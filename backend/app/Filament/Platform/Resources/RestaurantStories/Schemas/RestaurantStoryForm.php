<?php

namespace App\Filament\Platform\Resources\RestaurantStories\Schemas;

use App\Filament\Support\RestaurantStoryContentRepeater;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantStory;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class RestaurantStoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basics')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->options(fn () => Restaurant::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (Set $set) => $set('branch_id', null)),

                        Select::make('branch_id')
                            ->label('Branch')
                            ->options(function (Get $get) {
                                $restaurantId = $get('restaurant_id');
                                if (! $restaurantId) {
                                    return [];
                                }

                                return Branch::query()
                                    ->where('restaurant_id', $restaurantId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->nullable()
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, $fail) use ($get): void {
                                    if (! $value) {
                                        return;
                                    }

                                    $restaurantId = $get('restaurant_id');
                                    if (! $restaurantId) {
                                        $fail('Select a restaurant first.');

                                        return;
                                    }

                                    $ok = Branch::query()
                                        ->whereKey($value)
                                        ->where('restaurant_id', $restaurantId)
                                        ->exists();

                                    if (! $ok) {
                                        $fail('The selected branch does not belong to the selected restaurant.');
                                    }
                                },
                            ]),
                    ]),

                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->reactive()
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                            $slug = $get('slug');
                            if (filled($slug)) {
                                return;
                            }

                            if (blank($state)) {
                                return;
                            }

                            $set('slug', Str::slug($state));
                        }),
                ]),

            Section::make('Schedule')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('lifetime_mode')
                            ->label('Lifetime mode')
                            ->required()
                            ->options([
                                RestaurantStory::LIFETIME_12H => '12 hours',
                                RestaurantStory::LIFETIME_24H => '24 hours',
                                RestaurantStory::LIFETIME_48H => '48 hours',
                                RestaurantStory::LIFETIME_MANUAL => 'Manual (starts/ends)',
                            ])
                            ->default(RestaurantStory::LIFETIME_24H)
                            ->reactive(),

                        Select::make('status')
                            ->required()
                            ->options(array_combine(RestaurantStory::STATUSES, RestaurantStory::STATUSES))
                            ->default(RestaurantStory::STATUS_DRAFT),
                    ]),

                    Grid::make(2)->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Starts at')
                            ->nullable()
                            ->seconds(false)
                            ->native(false)
                            ->reactive(),

                        DateTimePicker::make('ends_at')
                            ->label('Ends at')
                            ->nullable()
                            ->seconds(false)
                            ->native(false)
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, $fail) use ($get): void {
                                    $startsAt = $get('starts_at');
                                    if (! $value || ! $startsAt) {
                                        return;
                                    }

                                    $starts = Carbon::parse($startsAt);
                                    $ends = Carbon::parse($value);

                                    if ($ends->lessThanOrEqualTo($starts)) {
                                        $fail('Ends at must be after starts at.');
                                    }
                                },
                            ])
                            ->helperText('Manual mode: provide ends_at (and optionally starts_at). Other modes derive ends_at automatically when publishing.'),
                    ]),

                    TextInput::make('lifetime_hours')
                        ->label('Lifetime hours (manual shortcut)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(24 * 30)
                        ->nullable()
                        ->visible(fn (Get $get): bool => $get('lifetime_mode') === RestaurantStory::LIFETIME_MANUAL)
                        ->helperText('Optional: if Ends at is empty, we’ll use Starts at + these hours (defaults starts_at to now when publishing).'),
                ]),

            RestaurantStoryContentRepeater::section(),

            Section::make('Story link button')
                ->description('Optional link shown with the whole story (not per slide).')
                ->collapsed()
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('cta_label')
                            ->label('Button label')
                            ->maxLength(255)
                            ->nullable(),
                        TextInput::make('cta_url')
                            ->label('Link URL')
                            ->maxLength(2048)
                            ->nullable(),
                    ]),
                ]),

            Section::make('Publishing')
                ->description('Slug and ordering are rarely changed once live.')
                ->collapsed()
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('display_order')
                            ->label('Display order')
                            ->numeric()
                            ->required()
                            ->default(0),
                    ]),
                ]),

            Section::make('Notes')
                ->collapsed()
                ->schema([
                    Textarea::make('notes')
                        ->rows(4)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

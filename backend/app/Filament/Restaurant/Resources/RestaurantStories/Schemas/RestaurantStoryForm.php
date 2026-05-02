<?php

namespace App\Filament\Restaurant\Resources\RestaurantStories\Schemas;

use App\Filament\Support\FilamentSchemaLayout;
use App\Filament\Support\RestaurantStoryContentRepeater;
use App\Models\Branch;
use App\Models\RestaurantStory;
use App\Models\User;
use Filament\Facades\Filament;
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
use Illuminate\Validation\Rule;

class RestaurantStoryForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        $restaurantIds = $user?->scopedRestaurantIds() ?? [];
        $branchIds = $user?->scopedBranchIds() ?? [];

        $isBranchScoped = count($branchIds) > 0;

        $allowedStatuses = [
            RestaurantStory::STATUS_DRAFT,
            RestaurantStory::STATUS_PENDING_REVIEW,
            RestaurantStory::STATUS_CANCELLED,
        ];

        return FilamentSchemaLayout::stackSections($schema)->components([
            Section::make('Basics')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->relationship(
                                name: 'restaurant',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereIn('id', $restaurantIds),
                            )
                            ->required()
                            ->searchable()
                            ->default(count($restaurantIds) === 1 ? $restaurantIds[0] : null)
                            ->disabled(count($restaurantIds) === 1)
                            ->dehydrated(true)
                            ->reactive()
                            ->rule(Rule::in($restaurantIds))
                            ->afterStateUpdated(fn (Set $set) => $set('branch_id', null)),

                        Select::make('branch_id')
                            ->label('Branch'.($isBranchScoped ? '' : ' (optional)'))
                            ->options(function (Get $get) use ($branchIds) {
                                $restaurantId = $get('restaurant_id');
                                if (! $restaurantId) {
                                    return [];
                                }

                                $query = Branch::query()
                                    ->where('restaurant_id', $restaurantId)
                                    ->orderBy('name');

                                if (count($branchIds)) {
                                    $query->whereIn('id', $branchIds);
                                }

                                return $query->pluck('name', 'id')->all();
                            })
                            ->searchable()
                            ->default($isBranchScoped && count($branchIds) === 1 ? $branchIds[0] : null)
                            ->required($isBranchScoped)
                            ->nullable(! $isBranchScoped)
                            ->rule(function (Get $get) use ($branchIds) {
                                $restaurantId = $get('restaurant_id');

                                if (! $restaurantId) {
                                    return Rule::prohibitedIf(true);
                                }

                                $rule = Rule::exists(Branch::class, 'id')->where('restaurant_id', $restaurantId);

                                if (count($branchIds)) {
                                    $rule->whereIn('id', $branchIds);
                                }

                                return $rule;
                            }),
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
                ]),

            Section::make('Schedule')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
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
                            ->options(array_combine($allowedStatuses, $allowedStatuses))
                            ->default(RestaurantStory::STATUS_DRAFT),
                    ]),

                    Grid::make(3)->schema([
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
                            ]),
                    ]),

                    TextInput::make('lifetime_hours')
                        ->label('Lifetime hours (manual shortcut)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(24 * 30)
                        ->nullable()
                        ->visible(fn (Get $get): bool => $get('lifetime_mode') === RestaurantStory::LIFETIME_MANUAL)
                        ->helperText('Optional shortcut when Ends at is empty.'),
                ]),

            RestaurantStoryContentRepeater::section(),

            Section::make('Story link button')
                ->compact()
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
                ->compact()
                ->description('Slug and ordering are rarely changed once live.')
                ->collapsed()
                ->schema([
                    Grid::make(3)->schema([
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
                ->compact()
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

<?php

namespace App\Filament\Restaurant\Resources\RestaurantStories\Schemas;

use App\Models\Branch;
use App\Models\RestaurantStory;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

        return $schema->components([
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

            TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),

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

            Section::make('Legacy container fields (deprecated)')
                ->collapsed()
                ->schema([
                    Select::make('story_type')
                        ->label('Legacy story type')
                        ->reactive()
                        ->options(array_combine(RestaurantStory::STORY_TYPES, RestaurantStory::STORY_TYPES))
                        ->default(RestaurantStory::TYPE_IMAGE)
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            if ($state === RestaurantStory::TYPE_TEXT) {
                                $set('media_url', null);
                            } else {
                                $set('body', null);
                            }
                        }),

                    TextInput::make('media_url')
                        ->label('Legacy media URL')
                        ->nullable()
                        ->maxLength(2048),

                    Textarea::make('body')
                        ->label('Legacy body')
                        ->rows(6)
                        ->nullable(),
                ]),

            TextInput::make('cta_label')
                ->label('CTA label')
                ->maxLength(255)
                ->nullable(),

            TextInput::make('cta_url')
                ->label('CTA URL')
                ->maxLength(2048)
                ->nullable(),

            Select::make('status')
                ->required()
                ->options(array_combine($allowedStatuses, $allowedStatuses))
                ->default(RestaurantStory::STATUS_DRAFT),

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
                ->helperText('For manual lifetime mode, provide ends_at (and optionally starts_at). Other modes derive ends_at automatically when publishing/approving if ends_at is empty.'),

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

            TextInput::make('lifetime_hours')
                ->label('Lifetime hours (manual shortcut)')
                ->numeric()
                ->minValue(1)
                ->maxValue(24 * 30)
                ->nullable()
                ->visible(fn (Get $get): bool => $get('lifetime_mode') === RestaurantStory::LIFETIME_MANUAL)
                ->helperText('Optional: if Ends at is empty, we’ll use Starts at + these hours (defaults starts_at to now when publishing).'),

            TextInput::make('display_order')
                ->label('Display order')
                ->numeric()
                ->required()
                ->default(0),

            Textarea::make('notes')
                ->rows(4)
                ->nullable(),
        ]);
    }
}

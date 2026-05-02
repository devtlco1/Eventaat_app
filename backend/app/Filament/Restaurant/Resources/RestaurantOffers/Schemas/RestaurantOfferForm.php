<?php

namespace App\Filament\Restaurant\Resources\RestaurantOffers\Schemas;

use App\Models\Branch;
use App\Models\RestaurantOffer;
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

class RestaurantOfferForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        $restaurantIds = $user?->scopedRestaurantIds() ?? [];
        $branchIds = $user?->scopedBranchIds() ?? [];

        $isBranchScoped = count($branchIds) > 0;

        $allowedStatuses = [
            RestaurantOffer::STATUS_DRAFT,
            RestaurantOffer::STATUS_PENDING_REVIEW,
            RestaurantOffer::STATUS_CANCELLED,
        ];

        return $schema->components([
            Section::make('Basics')
                ->compact()
                ->schema([
                    Grid::make(2)->schema([
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
                    ]),
                    Grid::make(2)->schema([
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
                    ]),
                ]),

            Section::make('Offer setup')
                ->compact()
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('status')
                            ->required()
                            ->options(array_combine($allowedStatuses, $allowedStatuses))
                            ->default(RestaurantOffer::STATUS_DRAFT),

                        Select::make('offer_type')
                            ->label('Offer type')
                            ->required()
                            ->reactive()
                            ->options(array_combine(RestaurantOffer::OFFER_TYPES, RestaurantOffer::OFFER_TYPES))
                            ->default(RestaurantOffer::TYPE_TEXT_ONLY)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if ($state === RestaurantOffer::TYPE_TEXT_ONLY) {
                                    $set('discount_value', null);
                                }
                            }),
                    ]),
                    TextInput::make('discount_value')
                        ->label('Discount value')
                        ->numeric()
                        ->step('0.01')
                        ->nullable()
                        ->required(fn (Get $get): bool => in_array($get('offer_type'), [
                            RestaurantOffer::TYPE_PERCENTAGE,
                            RestaurantOffer::TYPE_FIXED_AMOUNT,
                        ], true))
                        ->rules([
                            fn (Get $get) => function (string $attribute, $value, $fail) use ($get): void {
                                $type = $get('offer_type');

                                if ($type === RestaurantOffer::TYPE_TEXT_ONLY) {
                                    return;
                                }

                                if ($value === null || $value === '') {
                                    $fail('Discount value is required.');

                                    return;
                                }

                                $number = (float) $value;

                                if ($type === RestaurantOffer::TYPE_PERCENTAGE) {
                                    if ($number < 1 || $number > 100) {
                                        $fail('Percentage discount must be between 1 and 100.');
                                    }
                                }

                                if ($type === RestaurantOffer::TYPE_FIXED_AMOUNT) {
                                    if ($number <= 0) {
                                        $fail('Fixed amount discount must be greater than 0.');
                                    }
                                }
                            },
                        ]),
                ]),

            Section::make('Schedule')
                ->compact()
                ->schema([
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
                            ]),
                    ]),
                ]),

            Section::make('Description & notes')
                ->compact()
                ->collapsed()
                ->schema([
                    Textarea::make('description')
                        ->rows(4)
                        ->nullable()
                        ->columnSpanFull(),
                    Textarea::make('terms')
                        ->rows(4)
                        ->nullable()
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->rows(4)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

<?php

namespace App\Filament\Platform\Resources\RestaurantOffers\Schemas;

use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantOffer;
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

class RestaurantOfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basics')
                ->compact()
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
                            ->options(array_combine(RestaurantOffer::STATUSES, RestaurantOffer::STATUSES))
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

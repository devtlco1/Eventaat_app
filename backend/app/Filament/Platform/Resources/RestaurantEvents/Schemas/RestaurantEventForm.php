<?php

namespace App\Filament\Platform\Resources\RestaurantEvents\Schemas;

use App\Filament\Support\FilamentSchemaLayout;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantEvent;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RestaurantEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)->components([
            Section::make('Basics')
                ->compact()
                ->schema([
                    Grid::make(4)->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (?string $state) => $state ? Str::slug($state) : null),
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->options(fn () => Restaurant::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required()
                            ->reactive(),
                        Select::make('branch_id')
                            ->label('Branch (optional)')
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
                            ->rule(function (Get $get) {
                                $restaurantId = $get('restaurant_id');

                                return $restaurantId
                                    ? Rule::exists(Branch::class, 'id')->where('restaurant_id', $restaurantId)
                                    : Rule::prohibitedIf(true);
                            }),
                    ]),
                ]),

            Section::make('Schedule & capacity')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('status')
                            ->required()
                            ->options(array_combine(RestaurantEvent::STATUSES, RestaurantEvent::STATUSES))
                            ->default(RestaurantEvent::STATUS_DRAFT),
                        Select::make('booking_mode')
                            ->required()
                            ->options(array_combine(RestaurantEvent::BOOKING_MODES, RestaurantEvent::BOOKING_MODES))
                            ->default(RestaurantEvent::BOOKING_MODE_INFO_ONLY),
                        TextInput::make('price_label')
                            ->maxLength(255)
                            ->nullable(),
                    ]),
                    Grid::make(3)->schema([
                        DateTimePicker::make('starts_at')
                            ->required(),
                        DateTimePicker::make('ends_at')
                            ->nullable()
                            ->rule('after:starts_at'),
                        TextInput::make('capacity')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),
                    ]),
                    Grid::make(3)->schema([
                        Placeholder::make('active_reserved_seats')
                            ->label('Active reserved seats')
                            ->content(fn (?RestaurantEvent $record) => $record ? (string) $record->activeReservedSeats() : '—'),
                        Placeholder::make('remaining_seats')
                            ->label('Remaining seats')
                            ->content(function (?RestaurantEvent $record) {
                                if (! $record) {
                                    return '—';
                                }

                                return $record->capacity === null ? 'Unlimited' : (string) $record->remainingSeats();
                            }),
                    ]),
                ]),

            Section::make('Description & notes')
                ->compact()
                ->collapsed()
                ->schema([
                    Textarea::make('description')
                        ->rows(5)
                        ->nullable()
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

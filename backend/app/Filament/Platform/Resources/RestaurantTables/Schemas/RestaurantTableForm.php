<?php

namespace App\Filament\Platform\Resources\RestaurantTables\Schemas;

use App\Enums\TableStatus;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\SeatingArea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class RestaurantTableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Details')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->options(fn () => Restaurant::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->reactive()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Select $component, $state, $record): void {
                                /** @var RestaurantTable|null $record */
                                if (! $record) {
                                    return;
                                }

                                $restaurantId = $record->seatingArea?->branch?->restaurant_id;
                                if ($restaurantId) {
                                    $component->state($restaurantId);
                                }
                            }),
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
                            ->reactive()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Select $component, $state, $record): void {
                                /** @var RestaurantTable|null $record */
                                if (! $record) {
                                    return;
                                }

                                $branchId = $record->seatingArea?->branch_id;
                                if ($branchId) {
                                    $component->state($branchId);
                                }
                            }),
                        Select::make('seating_area_id')
                            ->label('Seating Area')
                            ->options(function (Get $get) {
                                $branchId = $get('branch_id');
                                if (! $branchId) {
                                    return [];
                                }

                                return SeatingArea::query()
                                    ->where('branch_id', $branchId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->required()
                            ->searchable(),
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('label')
                            ->required()
                            ->maxLength(64),
                        TextInput::make('capacity')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(99)
                            ->default(2),
                        Select::make('status')
                            ->required()
                            ->options(array_combine(
                                array_map(fn (TableStatus $s) => $s->value, TableStatus::cases()),
                                array_map(fn (TableStatus $s) => $s->value, TableStatus::cases()),
                            ))
                            ->default(TableStatus::Active->value),
                    ]),
                ]),
        ]);
    }
}

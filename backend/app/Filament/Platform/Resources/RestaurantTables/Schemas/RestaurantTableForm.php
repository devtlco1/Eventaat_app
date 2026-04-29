<?php

namespace App\Filament\Platform\Resources\RestaurantTables\Schemas;

use App\Enums\TableStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RestaurantTableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('seating_area_id')
                    ->relationship('seatingArea', 'name')
                    ->required()
                    ->searchable(),
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
            ]);
    }
}

<?php

namespace App\Filament\Platform\Resources\SeatingAreas\Schemas;

use App\Enums\SeatingAreaType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SeatingAreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->required()
                    ->searchable(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->required()
                    ->maxLength(64),
                Select::make('type')
                    ->required()
                    ->options(array_combine(
                        array_map(fn (SeatingAreaType $t) => $t->value, SeatingAreaType::cases()),
                        array_map(fn (SeatingAreaType $t) => $t->value, SeatingAreaType::cases()),
                    ))
                    ->default(SeatingAreaType::Indoor->value),
                Select::make('status')
                    ->required()
                    ->options(['active' => 'active', 'inactive' => 'inactive'])
                    ->default('active'),
            ]);
    }
}

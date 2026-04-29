<?php

namespace App\Filament\Platform\Resources\Branches\Schemas;

use App\Enums\BranchStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('restaurant_id')
                    ->relationship('restaurant', 'name')
                    ->required()
                    ->searchable(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->required()
                    ->maxLength(64),
                Select::make('status')
                    ->required()
                    ->options(array_combine(
                        array_map(fn (BranchStatus $s) => $s->value, BranchStatus::cases()),
                        array_map(fn (BranchStatus $s) => $s->value, BranchStatus::cases()),
                    ))
                    ->default(BranchStatus::Active->value),
            ]);
    }
}

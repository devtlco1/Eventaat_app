<?php

namespace App\Filament\Platform\Resources\Branches\Schemas;

use App\Enums\BranchStatus;
use App\Filament\Support\FilamentSchemaLayout;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)->components([
            Section::make('Details')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
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
                    ]),
                    Grid::make(3)->schema([
                        Select::make('status')
                            ->required()
                            ->options(array_combine(
                                array_map(fn (BranchStatus $s) => $s->value, BranchStatus::cases()),
                                array_map(fn (BranchStatus $s) => $s->value, BranchStatus::cases()),
                            ))
                            ->default(BranchStatus::Active->value),
                    ]),
                ]),
        ]);
    }
}

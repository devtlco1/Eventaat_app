<?php

namespace App\Filament\Platform\Resources\SeatingAreas\RelationManagers;

use App\Enums\TableStatus;
use App\Filament\Support\FilamentSchemaLayout;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RestaurantTablesRelationManager extends RelationManager
{
    protected static string $relationship = 'tables';

    public function form(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)->components([
            TextInput::make('label')->required()->maxLength(64),
            TextInput::make('capacity')->numeric()->required()->minValue(1)->maxValue(99)->default(2),
            Select::make('status')
                ->required()
                ->options(array_combine(
                    array_map(fn (TableStatus $s) => $s->value, TableStatus::cases()),
                    array_map(fn (TableStatus $s) => $s->value, TableStatus::cases()),
                ))
                ->default(TableStatus::Active->value),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->searchable()->sortable(),
                TextColumn::make('capacity')->sortable(),
                TextColumn::make('status')->badge(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}

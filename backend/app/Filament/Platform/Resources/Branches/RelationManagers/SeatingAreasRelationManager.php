<?php

namespace App\Filament\Platform\Resources\Branches\RelationManagers;

use App\Enums\SeatingAreaType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;

class SeatingAreasRelationManager extends RelationManager
{
    protected static string $relationship = 'seatingAreas';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('code')->required()->maxLength(64),
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

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code')->searchable(),
                TextColumn::make('type')->badge(),
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


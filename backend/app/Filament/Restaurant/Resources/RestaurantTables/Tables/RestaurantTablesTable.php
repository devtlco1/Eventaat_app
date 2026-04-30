<?php

namespace App\Filament\Restaurant\Resources\RestaurantTables\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RestaurantTablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('seatingArea.branch.name')->label('Branch')->sortable(),
                TextColumn::make('seatingArea.name')->label('Seating Area')->sortable(),
                TextColumn::make('label')->searchable()->sortable(),
                TextColumn::make('capacity')->sortable(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}

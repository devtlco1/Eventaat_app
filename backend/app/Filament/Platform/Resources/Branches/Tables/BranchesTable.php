<?php

namespace App\Filament\Platform\Resources\Branches\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BranchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('restaurant.name')->label('Restaurant')->searchable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code')->searchable(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->relationship('restaurant', 'name'),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'active',
                        'inactive' => 'inactive',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}

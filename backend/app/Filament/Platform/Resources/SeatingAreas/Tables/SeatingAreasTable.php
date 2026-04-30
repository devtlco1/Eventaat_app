<?php

namespace App\Filament\Platform\Resources\SeatingAreas\Tables;

use App\Models\Restaurant;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SeatingAreasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('branch.restaurant.name')->label('Restaurant')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->searchable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code'),
                TextColumn::make('type')->badge(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->options(fn () => Restaurant::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $restaurantId) => $q->whereHas('branch', fn ($b) => $b->where('restaurant_id', $restaurantId))
                    )),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name'),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'active',
                        'inactive' => 'inactive',
                    ]),
                SelectFilter::make('type')
                    ->options([
                        'indoor' => 'indoor',
                        'outdoor' => 'outdoor',
                        'private_room' => 'private_room',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}

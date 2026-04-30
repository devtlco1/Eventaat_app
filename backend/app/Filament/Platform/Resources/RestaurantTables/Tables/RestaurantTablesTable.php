<?php

namespace App\Filament\Platform\Resources\RestaurantTables\Tables;

use App\Models\Restaurant;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RestaurantTablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('seatingArea.branch.restaurant.name')->label('Restaurant')->searchable()->sortable(),
                TextColumn::make('seatingArea.branch.name')->label('Branch')->searchable()->sortable(),
                TextColumn::make('seatingArea.name')->label('Seating Area')->searchable()->sortable(),
                TextColumn::make('label')->label('Table')->searchable()->sortable(),
                TextColumn::make('capacity')->sortable(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->options(fn () => Restaurant::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $restaurantId) => $q->whereHas('seatingArea.branch', fn ($b) => $b->where('restaurant_id', $restaurantId))
                    )),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $branchId) => $q->whereHas('seatingArea', fn ($a) => $a->where('branch_id', $branchId))
                    ))
                    ->options(function () {
                        // Keep branch options readable; uses branches table directly.
                        return \App\Models\Branch::query()->orderBy('name')->pluck('name', 'id')->all();
                    }),
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

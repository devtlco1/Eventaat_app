<?php

namespace App\Filament\Restaurant\Resources\RestaurantEvents\Tables;

use App\Models\RestaurantEvent;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RestaurantEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->sortable(),
                TextColumn::make('starts_at')->dateTime()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('booking_mode')->badge()->label('Booking mode')->sortable(),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(array_combine(RestaurantEvent::STATUSES, RestaurantEvent::STATUSES)),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}


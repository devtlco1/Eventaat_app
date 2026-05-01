<?php

namespace App\Filament\Platform\Resources\RestaurantEvents\Tables;

use App\Models\RestaurantEvent;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('restaurant.name')->label('Restaurant')->sortable(),
                TextColumn::make('branch.name')->label('Branch')->sortable(),
                TextColumn::make('starts_at')->dateTime()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('booking_mode')->badge()->label('Booking mode')->sortable(),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(array_combine(RestaurantEvent::STATUSES, RestaurantEvent::STATUSES)),
                SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->relationship('restaurant', 'name'),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name'),
                Filter::make('starts_at_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date')->label('Starts at (date)'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $date = $data['date'] ?? null;

                        return $date
                            ? $query->whereDate('starts_at', $date)
                            : $query;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}


<?php

namespace App\Filament\Platform\Resources\RestaurantSubscriptions\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RestaurantSubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('restaurant.name')->label('Restaurant')->searchable()->sortable(),
                TextColumn::make('subscriptionPlan.name')->label('Plan')->placeholder('—')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('starts_at')->dateTime()->sortable()->placeholder('—'),
                TextColumn::make('ends_at')->dateTime()->sortable()->placeholder('—'),
                TextColumn::make('cancelled_at')->dateTime()->sortable()->placeholder('—'),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

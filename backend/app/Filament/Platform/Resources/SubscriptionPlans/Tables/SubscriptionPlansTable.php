<?php

namespace App\Filament\Platform\Resources\SubscriptionPlans\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('price_amount')
                    ->label('Price')
                    ->money(fn ($record) => $record->currency ?? 'IQD')
                    ->sortable(),
                TextColumn::make('currency')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('billing_interval')->badge()->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
                TextColumn::make('display_order')->sortable(),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->defaultSort('display_order')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

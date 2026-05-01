<?php

namespace App\Filament\Platform\Resources\RestaurantEvents\RelationManagers;

use App\Enums\BookingStatus;
use App\Filament\Platform\Resources\Bookings\BookingResource;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Booking ID')->sortable(),
                TextColumn::make('customer.name')->label('Customer')->toggleable(),
                TextColumn::make('customer.phone')->label('Phone')->searchable(),
                TextColumn::make('branch.name')->label('Branch')->sortable(),
                TextColumn::make('starts_at')->dateTime()->sortable(),
                TextColumn::make('party_size')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof BookingStatus) {
                            return $state->label();
                        }

                        return BookingStatus::tryFrom((string) $state)?->label() ?? (string) $state;
                    })
                    ->sortable(),
                TextColumn::make('created_at')->since(),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                EditAction::make()
                    ->url(fn ($record) => BookingResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                //
            ]);
    }
}


<?php

namespace App\Filament\Restaurant\Resources\RestaurantEvents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RestaurantEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('title'),
            TextEntry::make('slug'),
            TextEntry::make('restaurant.name')->label('Restaurant'),
            TextEntry::make('branch.name')->label('Branch'),
            TextEntry::make('status')->badge(),
            TextEntry::make('booking_mode')->badge()->label('Booking mode'),
            TextEntry::make('starts_at')->dateTime(),
            TextEntry::make('ends_at')->dateTime(),
            TextEntry::make('capacity'),
            TextEntry::make('price_label')->label('Price'),
            TextEntry::make('description')->markdown()->columnSpanFull(),
            TextEntry::make('notes')->markdown()->columnSpanFull(),
            TextEntry::make('active_reserved_seats')
                ->label('Active reserved seats')
                ->state(fn ($record) => $record?->activeReservedSeats()),
            TextEntry::make('remaining_seats')
                ->label('Remaining seats')
                ->state(function ($record) {
                    if (! $record) {
                        return null;
                    }

                    return $record->capacity === null ? 'Unlimited' : $record->remainingSeats();
                }),
        ]);
    }
}


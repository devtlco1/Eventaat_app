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
        ]);
    }
}


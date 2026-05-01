<?php

namespace App\Filament\Restaurant\Resources\RestaurantOffers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RestaurantOfferInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title'),
                TextEntry::make('slug'),
                TextEntry::make('restaurant.name')->label('Restaurant'),
                TextEntry::make('branch.name')->label('Branch'),
                TextEntry::make('status')->badge(),
                TextEntry::make('offer_type')->label('Offer type')->badge(),
                TextEntry::make('discount_value')->label('Discount value'),
                TextEntry::make('starts_at')->label('Starts at')->dateTime(),
                TextEntry::make('ends_at')->label('Ends at')->dateTime(),
                TextEntry::make('description')->markdown(),
                TextEntry::make('terms')->markdown(),
                TextEntry::make('notes')->markdown(),
            ]);
    }
}


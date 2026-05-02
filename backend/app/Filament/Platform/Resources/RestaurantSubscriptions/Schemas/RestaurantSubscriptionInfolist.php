<?php

namespace App\Filament\Platform\Resources\RestaurantSubscriptions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantSubscriptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Subscription')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('restaurant.name')->label('Restaurant'),
                        TextEntry::make('subscriptionPlan.name')->label('Plan')->placeholder('—'),
                        TextEntry::make('status')->badge(),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('starts_at')->dateTime()->placeholder('—'),
                        TextEntry::make('ends_at')->dateTime()->placeholder('—'),
                        TextEntry::make('cancelled_at')->dateTime()->placeholder('—'),
                    ]),
                    TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }
}

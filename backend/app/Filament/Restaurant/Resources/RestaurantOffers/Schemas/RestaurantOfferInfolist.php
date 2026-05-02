<?php

namespace App\Filament\Restaurant\Resources\RestaurantOffers\Schemas;

use App\Filament\Support\FilamentSchemaLayout;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantOfferInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('title'),
                            TextEntry::make('slug'),
                            TextEntry::make('restaurant.name')->label('Restaurant'),
                            TextEntry::make('branch.name')->label('Branch')->placeholder('—'),
                        ]),
                    ]),

                Section::make('Status')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('status')->badge(),
                            TextEntry::make('offer_type')->label('Offer type')->badge(),
                            TextEntry::make('discount_value')->label('Discount value')->placeholder('—'),
                        ]),
                    ]),

                Section::make('Timing')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('starts_at')->label('Starts at')->dateTime()->placeholder('—'),
                            TextEntry::make('ends_at')->label('Ends at')->dateTime()->placeholder('—'),
                        ]),
                    ]),

                Section::make('Content')
                    ->schema([
                        TextEntry::make('description')->markdown()->columnSpanFull()->placeholder('—'),
                        TextEntry::make('terms')->markdown()->columnSpanFull()->placeholder('—'),
                        TextEntry::make('notes')->markdown()->columnSpanFull()->placeholder('—'),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Restaurant\Resources\RestaurantTables\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantTableInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('seatingArea.branch.restaurant.name')->label('Restaurant'),
                            TextEntry::make('seatingArea.branch.name')->label('Branch'),
                            TextEntry::make('seatingArea.name')->label('Seating area'),
                            TextEntry::make('label')->label('Table label'),
                            TextEntry::make('capacity'),
                            TextEntry::make('status')->badge(),
                        ]),
                    ]),
            ]);
    }
}

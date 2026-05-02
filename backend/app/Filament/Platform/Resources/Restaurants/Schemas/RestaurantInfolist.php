<?php

namespace App\Filament\Platform\Resources\Restaurants\Schemas;

use App\Filament\Support\FilamentSchemaLayout;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)
            ->components([
                Section::make('Details')
                    ->compact()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('name'),
                            TextEntry::make('slug'),
                            TextEntry::make('status')->badge(),
                        ]),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Restaurant\Resources\Branches\Schemas;

use App\Filament\Support\FilamentSchemaLayout;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('restaurant.name')->label('Restaurant'),
                            TextEntry::make('name'),
                            TextEntry::make('code'),
                            TextEntry::make('status')->badge(),
                        ]),
                    ]),
            ]);
    }
}

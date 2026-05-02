<?php

namespace App\Filament\Platform\Resources\SeatingAreas\Schemas;

use App\Filament\Support\FilamentSchemaLayout;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SeatingAreaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('branch.restaurant.name')->label('Restaurant'),
                            TextEntry::make('branch.name')->label('Branch'),
                            TextEntry::make('name'),
                            TextEntry::make('code'),
                            TextEntry::make('type')->badge(),
                            TextEntry::make('status')->badge(),
                        ]),
                    ]),
            ]);
    }
}

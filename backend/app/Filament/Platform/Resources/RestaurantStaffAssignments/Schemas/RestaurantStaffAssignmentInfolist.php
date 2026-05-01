<?php

namespace App\Filament\Platform\Resources\RestaurantStaffAssignments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantStaffAssignmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('user.email')->label('User'),
                            TextEntry::make('restaurant.name')->label('Restaurant'),
                            TextEntry::make('branch.name')->label('Branch')->placeholder('—'),
                            TextEntry::make('role')->badge(),
                            TextEntry::make('status')->badge(),
                            TextEntry::make('created_at')->dateTime(),
                        ]),
                    ]),
            ]);
    }
}

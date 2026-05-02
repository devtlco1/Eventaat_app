<?php

namespace App\Filament\Platform\Resources\SubscriptionPlans\Schemas;

use App\Filament\Support\FilamentSchemaLayout;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionPlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)->components([
            Section::make('Plan')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('name'),
                        TextEntry::make('slug')->badge(),
                        TextEntry::make('is_active')->label('Active')->badge(),
                        TextEntry::make('display_order'),
                    ]),
                    TextEntry::make('description')->placeholder('—')->columnSpanFull(),
                    Grid::make(3)->schema([
                        TextEntry::make('price_amount')->money(fn ($record) => $record->currency ?? 'IQD'),
                        TextEntry::make('currency'),
                        TextEntry::make('billing_interval')->badge(),
                    ]),
                ]),
        ]);
    }
}

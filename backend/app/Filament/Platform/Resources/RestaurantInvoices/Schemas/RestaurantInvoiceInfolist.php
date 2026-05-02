<?php

namespace App\Filament\Platform\Resources\RestaurantInvoices\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantInvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Invoice')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('invoice_number')->badge(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('restaurant.name')->label('Restaurant'),
                        TextEntry::make('restaurantSubscription.subscriptionPlan.name')->label('Plan')->placeholder('—'),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('issue_date')->date()->placeholder('—'),
                        TextEntry::make('due_date')->date()->placeholder('—'),
                        TextEntry::make('paid_at')->dateTime()->placeholder('—'),
                    ]),
                ]),
            Section::make('Amounts')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('subtotal_amount')->money(fn ($record) => $record->currency ?? 'IQD'),
                        TextEntry::make('discount_amount')->money(fn ($record) => $record->currency ?? 'IQD'),
                        TextEntry::make('tax_amount')->money(fn ($record) => $record->currency ?? 'IQD'),
                        TextEntry::make('total_amount')->money(fn ($record) => $record->currency ?? 'IQD')->weight('bold'),
                        TextEntry::make('currency'),
                    ]),
                ]),
            Section::make('Notes')
                ->collapsed()
                ->schema([
                    TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }
}

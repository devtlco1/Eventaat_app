<?php

namespace App\Filament\Platform\Resources\SupportTickets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupportTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket')
                    ->schema([
                        TextEntry::make('subject')->columnSpanFull(),
                        Grid::make(4)->schema([
                            TextEntry::make('category')->badge(),
                            TextEntry::make('priority')->badge(),
                            TextEntry::make('status')->badge(),
                            TextEntry::make('source')->badge(),
                        ]),
                    ]),

                Section::make('Restaurant & booking')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('restaurant.name')->label('Restaurant')->placeholder('Platform-level'),
                            TextEntry::make('branch.name')->label('Branch')->placeholder('—'),
                            TextEntry::make('booking.id')->label('Booking')->placeholder('—'),
                        ]),
                    ]),

                Section::make('Customer')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('customer_name')->label('Customer name')->placeholder('—'),
                            TextEntry::make('customer_phone')->label('Customer phone')->placeholder('—'),
                            TextEntry::make('user.name')->label('Linked user')->placeholder('—'),
                        ]),
                    ]),

                Section::make('Content')
                    ->schema([
                        TextEntry::make('message')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('internal_notes')->label('Internal notes')->columnSpanFull()->placeholder('—'),
                    ]),

                Section::make('Resolution')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('resolved_at')->label('Resolved at')->dateTime()->placeholder('—'),
                            TextEntry::make('closed_at')->label('Closed at')->dateTime()->placeholder('—'),
                        ]),
                    ]),

                Section::make('System')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('created_at')->dateTime(),
                            TextEntry::make('updated_at')->dateTime(),
                        ]),
                    ]),
            ]);
    }
}

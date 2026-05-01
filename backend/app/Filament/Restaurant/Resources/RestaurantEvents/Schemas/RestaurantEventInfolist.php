<?php

namespace App\Filament\Restaurant\Resources\RestaurantEvents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Details')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('title'),
                        TextEntry::make('slug'),
                        TextEntry::make('restaurant.name')->label('Restaurant'),
                        TextEntry::make('branch.name')->label('Branch')->placeholder('—'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('booking_mode')->badge()->label('Booking mode'),
                        TextEntry::make('starts_at')->dateTime(),
                        TextEntry::make('ends_at')->dateTime()->placeholder('—'),
                        TextEntry::make('price_label')->label('Price')->placeholder('—'),
                    ]),
                ]),

            Section::make('Capacity')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('capacity')
                            ->formatStateUsing(fn ($state) => $state === null ? 'Unlimited' : (string) $state),
                        TextEntry::make('active_reserved_seats')
                            ->label('Active reserved seats')
                            ->state(fn ($record) => $record?->activeReservedSeats()),
                        TextEntry::make('remaining_seats')
                            ->label('Remaining seats')
                            ->state(function ($record) {
                                if (! $record) {
                                    return null;
                                }

                                return $record->capacity === null ? 'Unlimited' : $record->remainingSeats();
                            }),
                    ]),
                ]),

            Section::make('Notes')
                ->schema([
                    TextEntry::make('description')->markdown()->columnSpanFull()->placeholder('—'),
                    TextEntry::make('notes')->markdown()->columnSpanFull()->placeholder('—'),
                ]),
        ]);
    }
}


<?php

namespace App\Filament\Platform\Resources\RestaurantReviews\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Review')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('rating')->label('Rating')->badge(),
                            TextEntry::make('status')->label('Status')->badge(),
                            TextEntry::make('source')->label('Source')->badge(),
                        ]),
                        TextEntry::make('comment')
                            ->label('Comment')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ]),

                Section::make('Restaurant & booking')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('restaurant.name')->label('Restaurant'),
                            TextEntry::make('branch.name')->label('Branch')->placeholder('Restaurant-wide'),
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

                Section::make('Admin notes')
                    ->collapsed()
                    ->schema([
                        TextEntry::make('admin_notes')
                            ->columnSpanFull()
                            ->placeholder('—'),
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

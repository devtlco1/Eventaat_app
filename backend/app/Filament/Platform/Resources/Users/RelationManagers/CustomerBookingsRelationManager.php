<?php

namespace App\Filament\Platform\Resources\Users\RelationManagers;

use App\Enums\BookingStatus;
use App\Filament\Platform\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerBookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'customerBookings';

    protected static ?string $title = 'Bookings';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('restaurant.name')->label('Restaurant')->placeholder('—'),
                TextColumn::make('branch.name')->label('Branch')->placeholder('—'),
                TextColumn::make('starts_at')->dateTime()->sortable(),
                TextColumn::make('party_size')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof BookingStatus) {
                            return $state->label();
                        }

                        return BookingStatus::tryFrom((string) $state)?->label() ?? (string) $state;
                    }),
            ])
            ->defaultSort('starts_at', 'desc')
            ->paginated([10, 25])
            ->recordActions([])
            ->headerActions([])
            ->bulkActions([])
            ->recordUrl(fn (Booking $record): string => BookingResource::getUrl('edit', ['record' => $record]));
    }
}

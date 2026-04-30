<?php

namespace App\Filament\Platform\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\Bookings\BookingTransitionService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('starts_at')->dateTime()->sortable(),
                TextColumn::make('party_size')->sortable(),
                TextColumn::make('restaurant.name')->label('Restaurant')->sortable(),
                TextColumn::make('branch.name')->label('Branch')->sortable(),
                TextColumn::make('customer.phone')->label('Customer phone')->searchable(),
                TextColumn::make('created_at')->since(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(array_combine(
                        array_map(fn (BookingStatus $s) => $s->value, BookingStatus::cases()),
                        array_map(fn (BookingStatus $s) => $s->value, BookingStatus::cases()),
                    )),
                SelectFilter::make('restaurant')
                    ->relationship('restaurant', 'name'),
                SelectFilter::make('branch')
                    ->relationship('branch', 'name'),
            ])
            ->recordActions([
                Action::make('accept')
                    ->label('Accept')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canAccept($record))
                    ->action(fn (Booking $record) => app(BookingTransitionService::class)->accept($record)),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canReject($record))
                    ->action(fn (Booking $record) => app(BookingTransitionService::class)->reject($record)),
                Action::make('cancel')
                    ->label('Cancel')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canCancel($record))
                    ->action(fn (Booking $record) => app(BookingTransitionService::class)->cancel($record)),
                EditAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}

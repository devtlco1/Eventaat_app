<?php

namespace App\Filament\Restaurant\Resources\Bookings\Tables;

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
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof BookingStatus) {
                            return $state->label();
                        }

                        return BookingStatus::tryFrom((string) $state)?->label() ?? (string) $state;
                    })
                    ->sortable(),
                TextColumn::make('starts_at')->dateTime()->sortable(),
                TextColumn::make('party_size')->sortable(),
                TextColumn::make('branch.name')->label('Branch')->sortable(),
                TextColumn::make('seatingArea.name')->label('Seating Area')->sortable(),
                TextColumn::make('table.label')->label('Table')->sortable(),
                TextColumn::make('customer.phone')->label('Customer phone')->searchable(),
                TextColumn::make('created_at')->since(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(array_combine(
                        array_map(fn (BookingStatus $s) => $s->value, BookingStatus::cases()),
                        array_map(fn (BookingStatus $s) => $s->value, BookingStatus::cases()),
                    )),
            ])
            ->recordActions([
                Action::make('accept')
                    ->label('Accept')
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize(fn (Booking $record) => \App\Filament\Restaurant\Resources\Bookings\BookingResource::canEdit($record))
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canAccept($record))
                    ->action(fn (Booking $record) => app(BookingTransitionService::class)->accept($record)),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize(fn (Booking $record) => \App\Filament\Restaurant\Resources\Bookings\BookingResource::canEdit($record))
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canReject($record))
                    ->action(fn (Booking $record) => app(BookingTransitionService::class)->reject($record)),
                Action::make('cancel')
                    ->label('Cancel')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->authorize(fn (Booking $record) => \App\Filament\Restaurant\Resources\Bookings\BookingResource::canEdit($record))
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canCancel($record))
                    ->action(fn (Booking $record) => app(BookingTransitionService::class)->cancel($record)),
                Action::make('arrive')
                    ->label('Mark arrived')
                    ->color('info')
                    ->requiresConfirmation()
                    ->authorize(fn (Booking $record) => \App\Filament\Restaurant\Resources\Bookings\BookingResource::canEdit($record))
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canArrive($record))
                    ->action(fn (Booking $record) => app(BookingTransitionService::class)->arrive($record)),
                Action::make('seat')
                    ->label('Mark seated')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->authorize(fn (Booking $record) => \App\Filament\Restaurant\Resources\Bookings\BookingResource::canEdit($record))
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canSeat($record))
                    ->action(fn (Booking $record) => app(BookingTransitionService::class)->seat($record)),
                Action::make('complete')
                    ->label('Mark completed')
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize(fn (Booking $record) => \App\Filament\Restaurant\Resources\Bookings\BookingResource::canEdit($record))
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canComplete($record))
                    ->action(fn (Booking $record) => app(BookingTransitionService::class)->complete($record)),
                Action::make('no_show')
                    ->label('Mark no-show')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize(fn (Booking $record) => \App\Filament\Restaurant\Resources\Bookings\BookingResource::canEdit($record))
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canNoShow($record))
                    ->action(fn (Booking $record) => app(BookingTransitionService::class)->noShow($record)),
                EditAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}

<?php

namespace App\Filament\Platform\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\Bookings\BookingTransitionException;
use App\Services\Bookings\BookingTransitionService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                TextColumn::make('restaurantEvent.title')->label('Event')->toggleable(isToggledHiddenByDefault: true)->limit(30),
                TextColumn::make('restaurant.name')->label('Restaurant')->sortable(),
                TextColumn::make('branch.name')->label('Branch')->sortable(),
                TextColumn::make('table.label')->label('Table')->sortable(),
                TextColumn::make('customer.name')->label('Customer')->toggleable(),
                TextColumn::make('customer.phone')->label('Customer phone')->searchable(),
                TextColumn::make('created_at')->since(),
            ])
            ->defaultSort('id', 'desc')
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
                Filter::make('starts_at_date')
                    ->form([
                        DatePicker::make('date')->label('Starts at (date)'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $date = $data['date'] ?? null;

                        return $date
                            ? $query->whereDate('starts_at', $date)
                            : $query;
                    }),
            ])
            ->recordActions([
                Action::make('accept')
                    ->label('Accept')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canAccept($record))
                    ->action(function (Booking $record): void {
                        try {
                            app(BookingTransitionService::class)->accept($record);
                            Notification::make()->title('Booking accepted')->success()->send();
                        } catch (BookingTransitionException $e) {
                            Notification::make()->title('Cannot accept booking')->danger()->body($e->getMessage())->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canReject($record))
                    ->action(function (Booking $record): void {
                        try {
                            app(BookingTransitionService::class)->reject($record);
                            Notification::make()->title('Booking rejected')->success()->send();
                        } catch (BookingTransitionException $e) {
                            Notification::make()->title('Cannot reject booking')->danger()->body($e->getMessage())->send();
                        }
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canCancel($record))
                    ->action(function (Booking $record): void {
                        try {
                            app(BookingTransitionService::class)->cancel($record);
                            Notification::make()->title('Booking cancelled')->success()->send();
                        } catch (BookingTransitionException $e) {
                            Notification::make()->title('Cannot cancel booking')->danger()->body($e->getMessage())->send();
                        }
                    }),
                Action::make('arrive')
                    ->label('Mark arrived')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canArrive($record))
                    ->action(function (Booking $record): void {
                        try {
                            app(BookingTransitionService::class)->arrive($record);
                            Notification::make()->title('Marked arrived')->success()->send();
                        } catch (BookingTransitionException $e) {
                            Notification::make()->title('Cannot mark arrived')->danger()->body($e->getMessage())->send();
                        }
                    }),
                Action::make('seat')
                    ->label('Mark seated')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canSeat($record))
                    ->action(function (Booking $record): void {
                        try {
                            app(BookingTransitionService::class)->seat($record);
                            Notification::make()->title('Marked seated')->success()->send();
                        } catch (BookingTransitionException $e) {
                            Notification::make()->title('Cannot mark seated')->danger()->body($e->getMessage())->send();
                        }
                    }),
                Action::make('complete')
                    ->label('Mark completed')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canComplete($record))
                    ->action(function (Booking $record): void {
                        try {
                            app(BookingTransitionService::class)->complete($record);
                            Notification::make()->title('Marked completed')->success()->send();
                        } catch (BookingTransitionException $e) {
                            Notification::make()->title('Cannot mark completed')->danger()->body($e->getMessage())->send();
                        }
                    }),
                Action::make('no_show')
                    ->label('Mark no-show')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => app(BookingTransitionService::class)->canNoShow($record))
                    ->action(function (Booking $record): void {
                        try {
                            app(BookingTransitionService::class)->noShow($record);
                            Notification::make()->title('Marked no-show')->success()->send();
                        } catch (BookingTransitionException $e) {
                            Notification::make()->title('Cannot mark no-show')->danger()->body($e->getMessage())->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}

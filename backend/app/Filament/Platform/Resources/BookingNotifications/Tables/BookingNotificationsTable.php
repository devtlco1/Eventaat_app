<?php

namespace App\Filament\Platform\Resources\BookingNotifications\Tables;

use App\Models\BookingNotification;
use App\Services\Notifications\NotificationDispatchService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('booking_id')->label('Booking')->sortable(),
                TextColumn::make('event')->badge()->sortable()->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('channel')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('recipient_phone')->label('Phone')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')->limit(40)->searchable(),
                TextColumn::make('message')->label('Message')->limit(60)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->options(array_combine(BookingNotification::EVENTS, BookingNotification::EVENTS)),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'pending',
                        'sent' => 'sent',
                        'failed' => 'failed',
                        'skipped' => 'skipped',
                    ]),
                SelectFilter::make('channel')
                    ->options([
                        'internal' => 'internal',
                    ]),
            ])
            ->recordActions([
                Action::make('mark_sent')
                    ->label('Mark sent')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (BookingNotification $record): bool => $record->channel === 'internal' && $record->status === 'pending')
                    ->action(function (BookingNotification $record): void {
                        $ok = app(NotificationDispatchService::class)->markSent($record);

                        $n = Notification::make()->title($ok ? 'Marked sent' : 'No change');
                        ($ok ? $n->success() : $n->warning())->send();
                    }),
                Action::make('mark_skipped')
                    ->label('Mark skipped')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (BookingNotification $record): bool => $record->channel === 'internal' && $record->status === 'pending')
                    ->action(function (BookingNotification $record): void {
                        $ok = app(NotificationDispatchService::class)->markSkipped($record);

                        $n = Notification::make()->title($ok ? 'Marked skipped' : 'No change');
                        ($ok ? $n->success() : $n->warning())->send();
                    }),
                Action::make('mark_failed')
                    ->label('Mark failed')
                    ->color('danger')
                    ->visible(fn (BookingNotification $record): bool => $record->channel === 'internal' && $record->status === 'pending')
                    ->form([
                        Textarea::make('reason')
                            ->label('Failure reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (BookingNotification $record, array $data): void {
                        $ok = app(NotificationDispatchService::class)->markFailed($record, (string) ($data['reason'] ?? ''));

                        $n = Notification::make()->title($ok ? 'Marked failed' : 'No change');
                        ($ok ? $n->success() : $n->warning())->send();
                    }),
            ]);
    }
}

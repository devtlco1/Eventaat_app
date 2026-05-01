<?php

namespace App\Filament\Platform\Resources\BookingNotifications\Tables;

use App\Models\BookingNotification;
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
            ])
            ->recordActions([]);
    }
}

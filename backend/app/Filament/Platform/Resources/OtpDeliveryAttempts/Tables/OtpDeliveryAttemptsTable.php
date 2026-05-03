<?php

namespace App\Filament\Platform\Resources\OtpDeliveryAttempts\Tables;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OtpDeliveryAttemptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('phone_masked')->label('Phone (masked)')->searchable(),
                TextColumn::make('driver')->badge()->sortable()->searchable(),
                TextColumn::make('channel')->badge()->sortable()->toggleable(),
                TextColumn::make('provider')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'delivered', 'sent' => 'success',
                        'failed', 'undelivered' => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('provider_message_sid')->label('Message SID')->searchable()->placeholder('—'),
                TextColumn::make('error_code')->searchable()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('error_message')
                    ->limit(48)
                    ->wrap()
                    ->fontFamily(FontFamily::Mono)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('driver')
                    ->options([
                        'log' => 'log',
                        'twilio_sms' => 'twilio_sms',
                        'twilio_whatsapp' => 'twilio_whatsapp',
                    ]),
                SelectFilter::make('channel')
                    ->options([
                        'log' => 'log',
                        'sms' => 'sms',
                        'whatsapp' => 'whatsapp',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'pending',
                        'sent' => 'sent',
                        'delivered' => 'delivered',
                        'undelivered' => 'undelivered',
                        'failed' => 'failed',
                    ]),
                Filter::make('created_between')
                    ->schema([
                        DatePicker::make('from')->label('Created from'),
                        DatePicker::make('until')->label('Created until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, string $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn (Builder $q, string $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }
}

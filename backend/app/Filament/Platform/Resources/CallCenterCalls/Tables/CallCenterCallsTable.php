<?php

namespace App\Filament\Platform\Resources\CallCenterCalls\Tables;

use App\Enums\CallCenterCallDirection;
use App\Enums\CallCenterCallOutcome;
use App\Enums\CallCenterCallReason;
use App\Models\CallCenterCall;
use App\Services\Operations\CallCenterCallService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CallCenterCallsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('direction')
                    ->badge()
                    ->formatStateUsing(fn (?CallCenterCallDirection $state): ?string => $state?->label())
                    ->color(fn (CallCenterCall $record): string => $record->direction->filamentColor())
                    ->sortable(),
                TextColumn::make('reason')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->color(fn (CallCenterCall $record): string => $record->reason->filamentColor())
                    ->sortable(),
                TextColumn::make('outcome')
                    ->badge()
                    ->formatStateUsing(fn (?CallCenterCallOutcome $state): ?string => $state?->label())
                    ->color(fn (CallCenterCall $record): string => $record->outcome->filamentColor())
                    ->sortable(),
                TextColumn::make('restaurant.name')->label('Restaurant')->searchable()->sortable()->placeholder('—'),
                TextColumn::make('booking_id')
                    ->label('Booking')
                    ->formatStateUsing(fn (?int $state): string => $state ? '#'.$state : '—')
                    ->sortable(),
                TextColumn::make('customerUser.name')->label('Customer')->searchable()->placeholder('—'),
                TextColumn::make('phone')->searchable()->placeholder('—'),
                TextColumn::make('follow_up_at')->dateTime()->sortable()->placeholder('—'),
                TextColumn::make('handledByUser.name')->label('Handled by')->searchable()->placeholder('—'),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('direction')
                    ->options(collect(CallCenterCallDirection::cases())->mapWithKeys(
                        fn (CallCenterCallDirection $c): array => [$c->value => $c->label()]
                    )->all()),
                SelectFilter::make('reason')
                    ->options(collect(CallCenterCallReason::cases())->mapWithKeys(
                        fn (CallCenterCallReason $c): array => [$c->value => $c->label()]
                    )->all()),
                SelectFilter::make('outcome')
                    ->options(collect(CallCenterCallOutcome::cases())->mapWithKeys(
                        fn (CallCenterCallOutcome $c): array => [$c->value => $c->label()]
                    )->all()),
                SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->relationship('restaurant', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('follow_up_due')
                    ->label('Follow-up due')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('follow_up_at')
                        ->where('follow_up_at', '<=', now())),
                Filter::make('follow_up_scheduled')
                    ->label('Follow-up scheduled')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('follow_up_at')
                        ->where('follow_up_at', '>', now())),
            ])
            ->recordActions([
                Action::make('mark_resolved')
                    ->label('Mark resolved')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (CallCenterCall $record): bool => $record->outcome !== CallCenterCallOutcome::Resolved)
                    ->action(function (CallCenterCall $record): void {
                        app(CallCenterCallService::class)->markResolved($record);
                        Notification::make()->title(__('Marked resolved'))->success()->send();
                    }),
                Action::make('mark_escalated')
                    ->label('Mark escalated')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (CallCenterCall $record): bool => $record->outcome !== CallCenterCallOutcome::Escalated)
                    ->action(function (CallCenterCall $record): void {
                        app(CallCenterCallService::class)->markEscalated($record);
                        Notification::make()->title(__('Marked escalated'))->success()->send();
                    }),
                Action::make('mark_no_answer')
                    ->label('Mark no answer')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (CallCenterCall $record): bool => $record->outcome !== CallCenterCallOutcome::NoAnswer)
                    ->action(function (CallCenterCall $record): void {
                        app(CallCenterCallService::class)->markNoAnswer($record);
                        Notification::make()->title(__('Marked no answer'))->success()->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

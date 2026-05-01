<?php

namespace App\Filament\Platform\Resources\SupportTickets\Tables;

use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\SupportTicket;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['restaurant', 'branch', 'user']))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('subject')->searchable()->sortable()->limit(40),
                TextColumn::make('restaurant.name')->label('Restaurant')->placeholder('—')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->placeholder('—')->searchable()->sortable(),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->placeholder('—')
                    ->formatStateUsing(function (?string $state, SupportTicket $record): string {
                        if (filled($state)) {
                            return $state;
                        }

                        return filled($record->user?->name) ? (string) $record->user->name : '—';
                    })
                    ->searchable(),
                TextColumn::make('category')->badge()->sortable(),
                TextColumn::make('priority')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(array_combine(SupportTicket::STATUSES, SupportTicket::STATUSES)),
                SelectFilter::make('priority')
                    ->options(array_combine(SupportTicket::PRIORITIES, SupportTicket::PRIORITIES)),
                SelectFilter::make('category')
                    ->options(array_combine(SupportTicket::CATEGORIES, SupportTicket::CATEGORIES)),
                SelectFilter::make('source')
                    ->options(array_combine(SupportTicket::SOURCES, SupportTicket::SOURCES)),
                SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->options(fn () => Restaurant::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $restaurantId) => $q->where('restaurant_id', $restaurantId),
                    )),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $branchId) => $q->where('branch_id', $branchId),
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('mark_in_progress')
                    ->label('Mark in progress')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (SupportTicket $record): bool => $record->status === SupportTicket::STATUS_OPEN)
                    ->action(function (SupportTicket $record): void {
                        $record->forceFill(['status' => SupportTicket::STATUS_IN_PROGRESS])->save();
                        Notification::make()->title('Ticket marked in progress')->success()->send();
                    }),
                Action::make('resolve')
                    ->label('Resolve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SupportTicket $record): bool => in_array($record->status, [
                        SupportTicket::STATUS_OPEN,
                        SupportTicket::STATUS_IN_PROGRESS,
                    ], true))
                    ->action(function (SupportTicket $record): void {
                        $record->forceFill(['status' => SupportTicket::STATUS_RESOLVED])->save();
                        Notification::make()->title('Ticket resolved')->success()->send();
                    }),
                Action::make('close')
                    ->label('Close')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (SupportTicket $record): bool => in_array($record->status, [
                        SupportTicket::STATUS_OPEN,
                        SupportTicket::STATUS_IN_PROGRESS,
                        SupportTicket::STATUS_RESOLVED,
                    ], true))
                    ->action(function (SupportTicket $record): void {
                        $record->forceFill(['status' => SupportTicket::STATUS_CLOSED])->save();
                        Notification::make()->title('Ticket closed')->success()->send();
                    }),
                Action::make('reopen')
                    ->label('Reopen')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (SupportTicket $record): bool => in_array($record->status, [
                        SupportTicket::STATUS_RESOLVED,
                        SupportTicket::STATUS_CLOSED,
                    ], true))
                    ->action(function (SupportTicket $record): void {
                        $record->forceFill(['status' => SupportTicket::STATUS_OPEN])->save();
                        Notification::make()->title('Ticket reopened')->success()->send();
                    }),
                DeleteAction::make(),
            ]);
    }
}

<?php

namespace App\Filament\Platform\Resources\RestaurantInvoices\Tables;

use App\Enums\RestaurantInvoiceStatus;
use App\Models\RestaurantInvoice;
use App\Services\Finance\RestaurantInvoiceService;
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

class RestaurantInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->searchable()->sortable(),
                TextColumn::make('restaurant.name')->label('Restaurant')->searchable()->sortable(),
                TextColumn::make('restaurantSubscription.subscriptionPlan.name')
                    ->label('Plan')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (RestaurantInvoice $record): string => $record->status->filamentColor())
                    ->sortable(),
                TextColumn::make('issue_date')->date()->sortable()->placeholder('—'),
                TextColumn::make('due_date')->date()->sortable()->placeholder('—'),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money(fn (RestaurantInvoice $record): string => $record->currency ?? 'IQD')
                    ->sortable(),
                TextColumn::make('paid_at')->dateTime()->sortable()->placeholder('—'),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(RestaurantInvoiceStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
                SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->relationship('restaurant', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('past_due_date')
                    ->label('Past due date')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '<', now()->toDateString())
                        ->whereNotIn('status', [
                            RestaurantInvoiceStatus::Paid->value,
                            RestaurantInvoiceStatus::Void->value,
                        ])),
            ])
            ->recordActions([
                Action::make('mark_issued')
                    ->label('Mark issued')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantInvoice $record): bool => $record->status === RestaurantInvoiceStatus::Draft)
                    ->action(function (RestaurantInvoice $record): void {
                        app(RestaurantInvoiceService::class)->markIssued($record);
                        Notification::make()->title(__('Marked issued'))->success()->send();
                    }),
                Action::make('mark_paid')
                    ->label('Mark paid')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantInvoice $record): bool => in_array($record->status, [
                        RestaurantInvoiceStatus::Issued,
                        RestaurantInvoiceStatus::Overdue,
                    ], true))
                    ->action(function (RestaurantInvoice $record): void {
                        app(RestaurantInvoiceService::class)->markPaid($record);
                        Notification::make()->title(__('Marked paid'))->success()->send();
                    }),
                Action::make('mark_void')
                    ->label('Void')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantInvoice $record): bool => in_array($record->status, [
                        RestaurantInvoiceStatus::Draft,
                        RestaurantInvoiceStatus::Issued,
                        RestaurantInvoiceStatus::Overdue,
                    ], true))
                    ->action(function (RestaurantInvoice $record): void {
                        app(RestaurantInvoiceService::class)->markVoid($record);
                        Notification::make()->title(__('Invoice voided'))->success()->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

<?php

namespace App\Filament\Platform\Resources\RestaurantReviews\Tables;

use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantReview;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('restaurant.name')->label('Restaurant')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->placeholder('Restaurant-wide')->searchable()->sortable(),
                TextColumn::make('rating')
                    ->label('Rating')
                    ->badge()
                    ->color(fn (RestaurantReview $record): string => match ((int) $record->rating) {
                        1, 2 => 'danger',
                        3 => 'warning',
                        4 => 'info',
                        5 => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (RestaurantReview $record): string => match ($record->status) {
                        RestaurantReview::STATUS_PENDING_REVIEW => 'warning',
                        RestaurantReview::STATUS_PUBLISHED => 'success',
                        RestaurantReview::STATUS_REJECTED => 'danger',
                        RestaurantReview::STATUS_HIDDEN => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('source')
                    ->badge()
                    ->color(fn (RestaurantReview $record): string => match ($record->source) {
                        RestaurantReview::SOURCE_MOBILE => 'info',
                        RestaurantReview::SOURCE_IMPORT => 'warning',
                        RestaurantReview::SOURCE_DASHBOARD => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('customer_name')->label('Customer')->placeholder('—')->searchable(),
                TextColumn::make('booking_id')->label('Booking')->placeholder('—')->sortable(),
                TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(array_combine(RestaurantReview::STATUSES, RestaurantReview::STATUSES)),
                SelectFilter::make('source')
                    ->options(array_combine(RestaurantReview::SOURCES, RestaurantReview::SOURCES)),
                SelectFilter::make('rating')
                    ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
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
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantReview $record): bool => in_array($record->status, [
                        RestaurantReview::STATUS_PENDING_REVIEW,
                        RestaurantReview::STATUS_HIDDEN,
                        RestaurantReview::STATUS_REJECTED,
                    ], true))
                    ->action(function (RestaurantReview $record): void {
                        $record->forceFill(['status' => RestaurantReview::STATUS_PUBLISHED])->save();
                        Notification::make()->title('Review approved')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantReview $record): bool => in_array($record->status, [
                        RestaurantReview::STATUS_PENDING_REVIEW,
                        RestaurantReview::STATUS_PUBLISHED,
                        RestaurantReview::STATUS_HIDDEN,
                    ], true))
                    ->action(function (RestaurantReview $record): void {
                        $record->forceFill(['status' => RestaurantReview::STATUS_REJECTED])->save();
                        Notification::make()->title('Review rejected')->success()->send();
                    }),
                Action::make('hide')
                    ->label('Hide')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantReview $record): bool => in_array($record->status, [
                        RestaurantReview::STATUS_PUBLISHED,
                        RestaurantReview::STATUS_PENDING_REVIEW,
                    ], true))
                    ->action(function (RestaurantReview $record): void {
                        $record->forceFill(['status' => RestaurantReview::STATUS_HIDDEN])->save();
                        Notification::make()->title('Review hidden')->success()->send();
                    }),
            ]);
    }
}

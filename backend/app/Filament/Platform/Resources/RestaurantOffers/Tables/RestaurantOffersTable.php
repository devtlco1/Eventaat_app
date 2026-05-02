<?php

namespace App\Filament\Platform\Resources\RestaurantOffers\Tables;

use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantOffer;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class RestaurantOffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('restaurant.name')->label('Restaurant')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->searchable()->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (RestaurantOffer $record): string => match ($record->status) {
                        RestaurantOffer::STATUS_DRAFT => 'gray',
                        RestaurantOffer::STATUS_PENDING_REVIEW => 'warning',
                        RestaurantOffer::STATUS_PUBLISHED => 'success',
                        RestaurantOffer::STATUS_REJECTED => 'danger',
                        RestaurantOffer::STATUS_EXPIRED => 'gray',
                        RestaurantOffer::STATUS_CANCELLED => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('offer_type')
                    ->label('Offer type')
                    ->badge()
                    ->color(fn (RestaurantOffer $record): string => match ($record->offer_type) {
                        RestaurantOffer::TYPE_PERCENTAGE => 'warning',
                        RestaurantOffer::TYPE_FIXED_AMOUNT => 'info',
                        RestaurantOffer::TYPE_TEXT_ONLY => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('discount_value')->label('Discount value')->sortable(),
                TextColumn::make('starts_at')->label('Starts at')->dateTime()->sortable(),
                TextColumn::make('ends_at')->label('Ends at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(array_combine(RestaurantOffer::STATUSES, RestaurantOffer::STATUSES)),
                SelectFilter::make('offer_type')
                    ->label('Offer type')
                    ->options(array_combine(RestaurantOffer::OFFER_TYPES, RestaurantOffer::OFFER_TYPES)),
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
                Filter::make('active_now')
                    ->label('Active now')
                    ->query(function (Builder $query): Builder {
                        $now = Carbon::now();

                        return $query
                            ->where(function (Builder $q) use ($now) {
                                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                            })
                            ->where(function (Builder $q) use ($now) {
                                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                            });
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantOffer $record): bool => $record->status === RestaurantOffer::STATUS_PENDING_REVIEW)
                    ->action(function (RestaurantOffer $record): void {
                        $record->forceFill(['status' => RestaurantOffer::STATUS_PUBLISHED])->save();
                        Notification::make()->title('Offer approved')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantOffer $record): bool => $record->status === RestaurantOffer::STATUS_PENDING_REVIEW)
                    ->action(function (RestaurantOffer $record): void {
                        $record->forceFill(['status' => RestaurantOffer::STATUS_REJECTED])->save();
                        Notification::make()->title('Offer rejected')->success()->send();
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantOffer $record): bool => in_array($record->status, [
                        RestaurantOffer::STATUS_DRAFT,
                        RestaurantOffer::STATUS_PENDING_REVIEW,
                        RestaurantOffer::STATUS_PUBLISHED,
                        RestaurantOffer::STATUS_REJECTED,
                    ], true))
                    ->action(function (RestaurantOffer $record): void {
                        $record->forceFill(['status' => RestaurantOffer::STATUS_CANCELLED])->save();
                        Notification::make()->title('Offer cancelled')->success()->send();
                    }),
            ]);
    }
}

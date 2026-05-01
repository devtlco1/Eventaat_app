<?php

namespace App\Filament\Restaurant\Resources\RestaurantOffers\Tables;

use App\Models\Restaurant;
use App\Models\RestaurantOffer;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
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
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        $restaurantIds = $user?->scopedRestaurantIds() ?? [];

        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('restaurant.name')->label('Restaurant')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->searchable()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('offer_type')->label('Offer type')->badge()->sortable(),
                TextColumn::make('discount_value')->label('Discount value')->sortable(),
                TextColumn::make('starts_at')->label('Starts at')->dateTime()->sortable(),
                TextColumn::make('ends_at')->label('Ends at')->dateTime()->sortable(),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(array_combine(RestaurantOffer::STATUSES, RestaurantOffer::STATUSES)),
                SelectFilter::make('offer_type')
                    ->label('Offer type')
                    ->options(array_combine(RestaurantOffer::OFFER_TYPES, RestaurantOffer::OFFER_TYPES)),
                SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->visible(count($restaurantIds) > 1)
                    ->options(fn () => Restaurant::query()->whereIn('id', $restaurantIds)->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $restaurantId) => $q->where('restaurant_id', $restaurantId),
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
                Action::make('submit_for_review')
                    ->label('Submit for review')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantOffer $record): bool => $record->status === RestaurantOffer::STATUS_DRAFT)
                    ->action(function (RestaurantOffer $record): void {
                        $record->forceFill(['status' => RestaurantOffer::STATUS_PENDING_REVIEW])->save();
                        Notification::make()->title('Submitted for review')->success()->send();
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (RestaurantOffer $record): bool => in_array($record->status, [
                        RestaurantOffer::STATUS_DRAFT,
                        RestaurantOffer::STATUS_PENDING_REVIEW,
                    ], true))
                    ->action(function (RestaurantOffer $record): void {
                        $record->forceFill(['status' => RestaurantOffer::STATUS_CANCELLED])->save();
                        Notification::make()->title('Offer cancelled')->success()->send();
                    }),
            ]);
    }
}


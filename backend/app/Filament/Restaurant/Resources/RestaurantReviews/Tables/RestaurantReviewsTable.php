<?php

namespace App\Filament\Restaurant\Resources\RestaurantReviews\Tables;

use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantReview;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantReviewsTable
{
    public static function configure(Table $table): Table
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        $restaurantIds = $user?->scopedRestaurantIds() ?? [];
        $branchIds = $user?->scopedBranchIds() ?? [];

        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('restaurant.name')->label('Restaurant')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->placeholder('Restaurant-wide')->searchable()->sortable(),
                TextColumn::make('rating')->label('Rating')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('source')->badge()->sortable(),
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
                    ->visible(count($restaurantIds) > 1)
                    ->options(fn () => Restaurant::query()->whereIn('id', $restaurantIds)->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $restaurantId) => $q->where('restaurant_id', $restaurantId),
                    )),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->visible(count($branchIds) > 1)
                    ->options(fn () => Branch::query()->whereIn('id', $branchIds)->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $branchId) => $q->where('branch_id', $branchId),
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}

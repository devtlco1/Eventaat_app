<?php

namespace App\Filament\Restaurant\Resources\SupportTickets\Tables;

use App\Models\Restaurant;
use App\Models\SupportTicket;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        $restaurantIds = $user?->scopedRestaurantIds() ?? [];

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
                    ->visible(count($restaurantIds) > 1)
                    ->options(fn () => Restaurant::query()->whereIn('id', $restaurantIds)->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $restaurantId) => $q->where('restaurant_id', $restaurantId),
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}

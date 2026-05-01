<?php

namespace App\Filament\Restaurant\Resources\RestaurantMenus\Tables;

use App\Filament\Restaurant\Resources\RestaurantMenus\RestaurantMenuResource;
use App\Models\Restaurant;
use App\Models\RestaurantMenu;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantMenusTable
{
    public static function configure(Table $table): Table
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        $restaurantIds = $user?->scopedRestaurantIds() ?? [];

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['restaurant', 'branch']))
            ->headerActions([
                Action::make('create')
                    ->label('Add menu')
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn (): string => RestaurantMenuResource::getUrl('create'))
                    ->visible(fn (): bool => RestaurantMenuResource::canCreate()),
            ])
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('restaurant.name')->label('Restaurant')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->placeholder('—')->searchable()->sortable(),
                TextColumn::make('menu_mode')->label('Mode')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('display_order')->label('Order')->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable()->sinceTooltip(),
            ])
            ->defaultSort('display_order')
            ->filters([
                SelectFilter::make('status')
                    ->options(array_combine(RestaurantMenu::STATUSES, RestaurantMenu::STATUSES)),
                SelectFilter::make('menu_mode')
                    ->label('Menu mode')
                    ->options(array_combine(RestaurantMenu::MENU_MODES, RestaurantMenu::MENU_MODES)),
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
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

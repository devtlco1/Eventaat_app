<?php

namespace App\Filament\Platform\Resources\RestaurantMenus\Tables;

use App\Filament\Platform\Resources\RestaurantMenus\RestaurantMenuResource;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantMenu;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select as FormsSelect;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantMenusTable
{
    public static function configure(Table $table): Table
    {
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
                TextColumn::make('menu_mode')
                    ->label('Mode')
                    ->badge()
                    ->color(fn (RestaurantMenu $record): string => match ($record->menu_mode) {
                        RestaurantMenu::MODE_STRUCTURED => 'info',
                        RestaurantMenu::MODE_PDF_UPLOAD => 'warning',
                        RestaurantMenu::MODE_EXTERNAL_LINK => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (RestaurantMenu $record): string => match ($record->status) {
                        RestaurantMenu::STATUS_DRAFT => 'gray',
                        RestaurantMenu::STATUS_PUBLISHED => 'success',
                        RestaurantMenu::STATUS_ARCHIVED => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
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
                Filter::make('menu_location')
                    ->label('Restaurant / branch')
                    ->schema([
                        Grid::make(2)->schema([
                            FormsSelect::make('restaurant_id')
                                ->label('Restaurant')
                                ->options(fn () => Restaurant::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->nullable()
                                ->live(),
                            FormsSelect::make('branch_id')
                                ->label('Branch')
                                ->options(function (Get $get): array {
                                    $restaurantId = $get('restaurant_id');
                                    if (! $restaurantId) {
                                        return [];
                                    }

                                    return Branch::query()
                                        ->where('restaurant_id', $restaurantId)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all();
                                })
                                ->searchable()
                                ->nullable()
                                ->visible(fn (Get $get): bool => filled($get('restaurant_id'))),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $restaurantId = $data['restaurant_id'] ?? null;
                        $branchId = $data['branch_id'] ?? null;

                        return $query
                            ->when($restaurantId, fn (Builder $q) => $q->where('restaurant_id', $restaurantId))
                            ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

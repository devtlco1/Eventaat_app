<?php

namespace App\Filament\Platform\Resources\RestaurantMenus\RelationManagers;

use App\Filament\Platform\Resources\RestaurantMenus\RestaurantMenuCategoryResource;
use App\Filament\Platform\Resources\RestaurantMenus\RestaurantMenuResource;
use App\Filament\Support\FilamentSchemaLayout;
use App\Filament\Support\RestaurantMenuItemFormSchema;
use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuItem;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RestaurantMenuCategoryItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Items';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (! $ownerRecord instanceof RestaurantMenuCategory) {
            return false;
        }

        $menu = $ownerRecord->relationLoaded('menu')
            ? $ownerRecord->menu
            : $ownerRecord->menu()->first();

        if (! $menu || ! $menu->isStructured()) {
            return false;
        }

        return RestaurantMenuCategoryResource::canView($ownerRecord);
    }

    public function form(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)->components(
            RestaurantMenuItemFormSchema::sections($this->getOwnerRecord()->getKey()),
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->striped()
            ->searchable(false)
            ->emptyStateHeading('No items yet')
            ->emptyStateDescription('Use Add item to create the first item.')
            ->emptyStateIcon(null)
            ->columns([
                ViewColumn::make('image_thumb')
                    ->label('Image')
                    ->view('filament.tables.columns.restaurant-menu-item-thumb'),
                TextColumn::make('name')->label('Name')->sortable()->wrap(),
                TextColumn::make('description')
                    ->label('Description')
                    ->placeholder('—')
                    ->limit(60)
                    ->tooltip(fn (RestaurantMenuItem $record): ?string => filled($record->description) ? $record->description : null)
                    ->wrap(),
                TextColumn::make('price')
                    ->label('Price')
                    ->placeholder('—')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => $state === null || $state === ''
                        ? '—'
                        : number_format((float) $state, 2)),
                TextColumn::make('currency')->label('Currency')->badge(),
                IconColumn::make('is_available')->label('Available')->boolean(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                TextColumn::make('display_order')->label('Order')->sortable()->alignCenter(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add item')
                    ->modalHeading('Add item')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->visible(fn (): bool => RestaurantMenuResource::canEdit($this->getOwnerRecord()->menu)),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->modalHeading('Edit item')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->visible(fn (): bool => RestaurantMenuResource::canEdit($this->getOwnerRecord()->menu)),
                DeleteAction::make()
                    ->label('Delete')
                    ->modalWidth(Width::Medium)
                    ->visible(fn (): bool => RestaurantMenuResource::canEdit($this->getOwnerRecord()->menu)),
            ])
            ->bulkActions([])
            ->defaultSort('display_order');
    }
}

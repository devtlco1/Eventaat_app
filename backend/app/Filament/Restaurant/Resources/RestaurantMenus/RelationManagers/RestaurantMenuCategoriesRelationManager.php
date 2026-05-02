<?php

namespace App\Filament\Restaurant\Resources\RestaurantMenus\RelationManagers;

use App\Filament\Restaurant\Resources\RestaurantMenus\Pages\EditRestaurantMenu;
use App\Filament\Restaurant\Resources\RestaurantMenus\RestaurantMenuCategoryResource;
use App\Filament\Restaurant\Resources\RestaurantMenus\RestaurantMenuResource;
use App\Filament\Support\RestaurantMenuCategoryFormSchema;
use App\Filament\Support\RestaurantMenuStructuredUi;
use App\Models\RestaurantMenu;
use App\Models\RestaurantMenuCategory;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RestaurantMenuCategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'categories';

    protected static ?string $title = 'Categories';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (! RestaurantMenuStructuredUi::SHOW_CATEGORIES_RELATION_MANAGER_FALLBACK) {
            return false;
        }

        if (! $ownerRecord instanceof RestaurantMenu || ! $ownerRecord->isStructured()) {
            return false;
        }

        return RestaurantMenuResource::canView($ownerRecord);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(RestaurantMenuCategoryFormSchema::sections());
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(fn (): ?string => $this->getPageClass() === EditRestaurantMenu::class
                ? 'Create a category, then open it to manage menu items.'
                : null)
            ->columns([
                TextColumn::make('display_order')->label('Order')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('updated_at')->dateTime()->sortable()->sinceTooltip(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add category')
                    ->visible(fn (): bool => RestaurantMenuResource::canEdit($this->getOwnerRecord())),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Open category')
                    ->tooltip('Manage items inside')
                    ->url(fn (RestaurantMenuCategory $record): string => RestaurantMenuCategoryResource::getUrl('edit', [
                        'record' => $record,
                        'menu' => $this->getOwnerRecord(),
                    ]))
                    ->visible(fn (): bool => RestaurantMenuResource::canEdit($this->getOwnerRecord())),
                DeleteAction::make()
                    ->visible(fn (): bool => RestaurantMenuResource::canEdit($this->getOwnerRecord())),
            ])
            ->bulkActions([])
            ->defaultSort('display_order');
    }
}

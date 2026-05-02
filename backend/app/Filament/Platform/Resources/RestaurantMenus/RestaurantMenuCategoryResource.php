<?php

namespace App\Filament\Platform\Resources\RestaurantMenus;

use App\Filament\Concerns\AuthorizesPlatformOperations;
use App\Filament\Platform\Resources\RestaurantMenus\Pages\EditRestaurantMenuCategory;
use App\Filament\Platform\Resources\RestaurantMenus\RelationManagers\RestaurantMenuCategoryItemsRelationManager;
use App\Filament\Platform\Resources\RestaurantMenus\Schemas\RestaurantMenuCategoryForm;
use App\Models\RestaurantMenu;
use App\Models\RestaurantMenuCategory;
use Filament\Resources\ParentResourceRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

class RestaurantMenuCategoryResource extends Resource
{
    use AuthorizesPlatformOperations;

    protected static ?string $model = RestaurantMenuCategory::class;

    protected static ?string $slug = 'categories';

    protected static ?string $parentResource = RestaurantMenuResource::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'category';

    protected static ?string $pluralModelLabel = 'categories';

    public static function getParentResourceRegistration(): ?ParentResourceRegistration
    {
        return RestaurantMenuResource::asParent(static::class)
            ->relationship('categories')
            ->inverseRelationship('menu');
    }

    protected static function menu(RestaurantMenuCategory $record): RestaurantMenu
    {
        return $record->relationLoaded('menu')
            ? $record->menu
            : RestaurantMenu::query()->findOrFail($record->restaurant_menu_id);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        if (! self::isPlatformUser() || ! $record instanceof RestaurantMenuCategory) {
            return false;
        }

        return RestaurantMenuResource::canEdit(self::menu($record));
    }

    public static function canView($record): bool
    {
        return static::canEdit($record);
    }

    public static function canDelete($record): bool
    {
        return static::canEdit($record);
    }

    public static function form(Schema $schema): Schema
    {
        return RestaurantMenuCategoryForm::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            RestaurantMenuCategoryItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'edit' => EditRestaurantMenuCategory::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Platform\Resources\RestaurantMenus;

use App\Filament\Concerns\GrantsPlatformOperationsCrud;
use App\Filament\Platform\Resources\RestaurantMenus\Pages\CreateRestaurantMenu;
use App\Filament\Platform\Resources\RestaurantMenus\Pages\EditRestaurantMenu;
use App\Filament\Platform\Resources\RestaurantMenus\Pages\ListRestaurantMenus;
use App\Filament\Platform\Resources\RestaurantMenus\Pages\ViewRestaurantMenu;
use App\Filament\Platform\Resources\RestaurantMenus\RelationManagers\RestaurantMenuCategoriesRelationManager;
use App\Filament\Platform\Resources\RestaurantMenus\Schemas\RestaurantMenuForm;
use App\Filament\Platform\Resources\RestaurantMenus\Schemas\RestaurantMenuInfolist;
use App\Filament\Platform\Resources\RestaurantMenus\Tables\RestaurantMenusTable;
use App\Models\RestaurantMenu;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RestaurantMenuResource extends Resource
{
    use GrantsPlatformOperationsCrud;

    protected static ?string $model = RestaurantMenu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 18;

    protected static ?string $navigationLabel = 'Menus';

    protected static ?string $modelLabel = 'menu';

    protected static ?string $pluralModelLabel = 'menus';

    public static function form(Schema $schema): Schema
    {
        return RestaurantMenuForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RestaurantMenuInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantMenusTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RestaurantMenuCategoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurantMenus::route('/'),
            'create' => CreateRestaurantMenu::route('/create'),
            'view' => ViewRestaurantMenu::route('/{record}'),
            'edit' => EditRestaurantMenu::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Restaurant\Resources\RestaurantMenus;

use App\Filament\Restaurant\Resources\RestaurantMenus\Pages\CreateRestaurantMenu;
use App\Filament\Restaurant\Resources\RestaurantMenus\Pages\EditRestaurantMenu;
use App\Filament\Restaurant\Resources\RestaurantMenus\Pages\ListRestaurantMenus;
use App\Filament\Restaurant\Resources\RestaurantMenus\Pages\ViewRestaurantMenu;
use App\Filament\Restaurant\Resources\RestaurantMenus\RelationManagers\RestaurantMenuCategoriesRelationManager;
use App\Filament\Restaurant\Resources\RestaurantMenus\Schemas\RestaurantMenuForm;
use App\Filament\Restaurant\Resources\RestaurantMenus\Schemas\RestaurantMenuInfolist;
use App\Filament\Restaurant\Resources\RestaurantMenus\Tables\RestaurantMenusTable;
use App\Models\RestaurantMenu;
use App\Models\User;
use App\Support\RestaurantPanelScope;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantMenuResource extends Resource
{
    protected static ?string $model = RestaurantMenu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Menus';

    protected static ?string $modelLabel = 'menu';

    protected static ?string $pluralModelLabel = 'menus';

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        return RestaurantPanelScope::menus($user);
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return (bool) $user?->isRestaurantStaff();
    }

    public static function canEdit($record): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if (! $user || ! $user->isRestaurantStaff()) {
            return false;
        }

        return self::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function canView($record): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return $user ? self::getEloquentQuery()->whereKey($record)->exists() : false;
    }

    public static function canDelete($record): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if (! $user || ! $user->isRestaurantOwner()) {
            return false;
        }

        return self::getEloquentQuery()->whereKey($record)->exists();
    }

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

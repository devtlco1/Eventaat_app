<?php

namespace App\Filament\Restaurant\Resources\RestaurantTables;

use App\Filament\Restaurant\Resources\RestaurantTables\Pages\CreateRestaurantTable;
use App\Filament\Restaurant\Resources\RestaurantTables\Pages\EditRestaurantTable;
use App\Filament\Restaurant\Resources\RestaurantTables\Pages\ListRestaurantTables;
use App\Filament\Restaurant\Resources\RestaurantTables\Pages\ViewRestaurantTable;
use App\Filament\Restaurant\Resources\RestaurantTables\Schemas\RestaurantTableForm;
use App\Filament\Restaurant\Resources\RestaurantTables\Schemas\RestaurantTableInfolist;
use App\Filament\Restaurant\Resources\RestaurantTables\Tables\RestaurantTablesTable;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Support\RestaurantPanelScope;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantTableResource extends Resource
{
    protected static ?string $model = RestaurantTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant Setup';

    protected static ?int $navigationSort = 40;

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        return RestaurantPanelScope::tables($user);
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return (bool) $user?->canManageRestaurantStructure();
    }

    public static function canEdit($record): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if (! $user || ! $user->canManageRestaurantStructure()) {
            return false;
        }

        return RestaurantPanelScope::tables($user)->whereKey($record)->exists();
    }

    public static function canView($record): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return $user
            ? RestaurantPanelScope::tables($user)->whereKey($record)->exists()
            : false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return RestaurantTableForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RestaurantTableInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantTablesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurantTables::route('/'),
            'create' => CreateRestaurantTable::route('/create'),
            'view' => ViewRestaurantTable::route('/{record}'),
            'edit' => EditRestaurantTable::route('/{record}/edit'),
        ];
    }
}

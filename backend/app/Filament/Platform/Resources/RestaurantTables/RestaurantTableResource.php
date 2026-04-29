<?php

namespace App\Filament\Platform\Resources\RestaurantTables;

use App\Filament\Platform\Resources\RestaurantTables\Pages\CreateRestaurantTable;
use App\Filament\Platform\Resources\RestaurantTables\Pages\EditRestaurantTable;
use App\Filament\Platform\Resources\RestaurantTables\Pages\ListRestaurantTables;
use App\Filament\Platform\Resources\RestaurantTables\Pages\ViewRestaurantTable;
use App\Filament\Platform\Resources\RestaurantTables\Schemas\RestaurantTableForm;
use App\Filament\Platform\Resources\RestaurantTables\Schemas\RestaurantTableInfolist;
use App\Filament\Platform\Resources\RestaurantTables\Tables\RestaurantTablesTable;
use App\Models\RestaurantTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RestaurantTableResource extends Resource
{
    protected static ?string $model = RestaurantTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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

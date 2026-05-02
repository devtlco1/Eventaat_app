<?php

namespace App\Filament\Platform\Resources\SeatingAreas;

use App\Filament\Concerns\GrantsPlatformOperationsCrud;
use App\Filament\Platform\Resources\SeatingAreas\Pages\CreateSeatingArea;
use App\Filament\Platform\Resources\SeatingAreas\Pages\EditSeatingArea;
use App\Filament\Platform\Resources\SeatingAreas\Pages\ListSeatingAreas;
use App\Filament\Platform\Resources\SeatingAreas\Pages\ViewSeatingArea;
use App\Filament\Platform\Resources\SeatingAreas\RelationManagers\RestaurantTablesRelationManager;
use App\Filament\Platform\Resources\SeatingAreas\Schemas\SeatingAreaForm;
use App\Filament\Platform\Resources\SeatingAreas\Schemas\SeatingAreaInfolist;
use App\Filament\Platform\Resources\SeatingAreas\Tables\SeatingAreasTable;
use App\Models\SeatingArea;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SeatingAreaResource extends Resource
{
    use GrantsPlatformOperationsCrud;

    protected static ?string $model = SeatingArea::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant Setup';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return SeatingAreaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SeatingAreaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeatingAreasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RestaurantTablesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeatingAreas::route('/'),
            'create' => CreateSeatingArea::route('/create'),
            'view' => ViewSeatingArea::route('/{record}'),
            'edit' => EditSeatingArea::route('/{record}/edit'),
        ];
    }
}

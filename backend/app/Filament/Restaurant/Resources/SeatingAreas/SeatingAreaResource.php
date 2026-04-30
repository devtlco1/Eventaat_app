<?php

namespace App\Filament\Restaurant\Resources\SeatingAreas;

use App\Filament\Restaurant\Resources\SeatingAreas\Pages\CreateSeatingArea;
use App\Filament\Restaurant\Resources\SeatingAreas\Pages\EditSeatingArea;
use App\Filament\Restaurant\Resources\SeatingAreas\Pages\ListSeatingAreas;
use App\Filament\Restaurant\Resources\SeatingAreas\Pages\ViewSeatingArea;
use App\Filament\Restaurant\Resources\SeatingAreas\Schemas\SeatingAreaForm;
use App\Filament\Restaurant\Resources\SeatingAreas\Schemas\SeatingAreaInfolist;
use App\Filament\Restaurant\Resources\SeatingAreas\Tables\SeatingAreasTable;
use App\Models\SeatingArea;
use App\Support\RestaurantPanelScope;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SeatingAreaResource extends Resource
{
    protected static ?string $model = SeatingArea::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant Setup';

    protected static ?int $navigationSort = 30;

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        return RestaurantPanelScope::seatingAreas($user);
    }

    public static function canCreate(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        return (bool) $user?->hasAnyRole(['restaurant_owner', 'branch_manager']);
    }

    public static function canEdit($record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        if (! $user || ! $user->hasAnyRole(['restaurant_owner', 'branch_manager'])) {
            return false;
        }

        return RestaurantPanelScope::seatingAreas($user)->whereKey($record)->exists();
    }

    public static function canView($record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        return $user
            ? RestaurantPanelScope::seatingAreas($user)->whereKey($record)->exists()
            : false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

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
            //
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

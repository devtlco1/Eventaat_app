<?php

namespace App\Filament\Restaurant\Resources\Restaurants;

use App\Filament\Restaurant\Resources\Restaurants\Pages\CreateRestaurant;
use App\Filament\Restaurant\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Restaurant\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Restaurant\Resources\Restaurants\Pages\ViewRestaurant;
use App\Filament\Restaurant\Resources\Restaurants\Schemas\RestaurantForm;
use App\Filament\Restaurant\Resources\Restaurants\Schemas\RestaurantInfolist;
use App\Filament\Restaurant\Resources\Restaurants\Tables\RestaurantsTable;
use App\Models\Restaurant;
use App\Support\RestaurantPanelScope;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        return RestaurantPanelScope::restaurants($user);
    }

    public static function canCreate(): bool
    {
        // Restaurants are managed by the platform in Phase 2.
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return RestaurantForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RestaurantInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantsTable::configure($table);
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
            'index' => ListRestaurants::route('/'),
            'create' => CreateRestaurant::route('/create'),
            'view' => ViewRestaurant::route('/{record}'),
            'edit' => EditRestaurant::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Restaurant\Resources\RestaurantOffers;

use App\Filament\Restaurant\Resources\RestaurantOffers\Pages\CreateRestaurantOffer;
use App\Filament\Restaurant\Resources\RestaurantOffers\Pages\EditRestaurantOffer;
use App\Filament\Restaurant\Resources\RestaurantOffers\Pages\ListRestaurantOffers;
use App\Filament\Restaurant\Resources\RestaurantOffers\Pages\ViewRestaurantOffer;
use App\Filament\Restaurant\Resources\RestaurantOffers\Schemas\RestaurantOfferForm;
use App\Filament\Restaurant\Resources\RestaurantOffers\Schemas\RestaurantOfferInfolist;
use App\Filament\Restaurant\Resources\RestaurantOffers\Tables\RestaurantOffersTable;
use App\Models\RestaurantOffer;
use App\Models\User;
use App\Support\RestaurantPanelScope;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantOfferResource extends Resource
{
    protected static ?string $model = RestaurantOffer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 31;

    protected static ?string $navigationLabel = 'Offers';

    protected static ?string $modelLabel = 'offer';

    protected static ?string $pluralModelLabel = 'offers';

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        return RestaurantPanelScope::offers($user);
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return (bool) $user?->hasAnyRole(['restaurant_owner', 'branch_manager', 'restaurant_host']);
    }

    public static function canEdit($record): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if (! $user || ! $user->hasAnyRole(['restaurant_owner', 'branch_manager', 'restaurant_host'])) {
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

        if (! $user || ! $user->hasRole('restaurant_owner')) {
            return false;
        }

        return self::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function form(Schema $schema): Schema
    {
        return RestaurantOfferForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RestaurantOfferInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantOffersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurantOffers::route('/'),
            'create' => CreateRestaurantOffer::route('/create'),
            'view' => ViewRestaurantOffer::route('/{record}'),
            'edit' => EditRestaurantOffer::route('/{record}/edit'),
        ];
    }
}


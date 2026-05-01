<?php

namespace App\Filament\Platform\Resources\RestaurantOffers;

use App\Filament\Platform\Resources\RestaurantOffers\Pages\CreateRestaurantOffer;
use App\Filament\Platform\Resources\RestaurantOffers\Pages\EditRestaurantOffer;
use App\Filament\Platform\Resources\RestaurantOffers\Pages\ListRestaurantOffers;
use App\Filament\Platform\Resources\RestaurantOffers\Pages\ViewRestaurantOffer;
use App\Filament\Platform\Resources\RestaurantOffers\Schemas\RestaurantOfferForm;
use App\Filament\Platform\Resources\RestaurantOffers\Schemas\RestaurantOfferInfolist;
use App\Filament\Platform\Resources\RestaurantOffers\Tables\RestaurantOffersTable;
use App\Models\RestaurantOffer;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RestaurantOfferResource extends Resource
{
    protected static ?string $model = RestaurantOffer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 18;

    protected static ?string $navigationLabel = 'Offers';

    protected static ?string $modelLabel = 'offer';

    protected static ?string $pluralModelLabel = 'offers';

    private static function isPlatformUser(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return (bool) $user?->hasAnyRole(['super_admin', 'operations_admin']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::isPlatformUser();
    }

    public static function canViewAny(): bool
    {
        return self::isPlatformUser();
    }

    public static function canCreate(): bool
    {
        return self::isPlatformUser();
    }

    public static function canEdit($record): bool
    {
        return self::isPlatformUser();
    }

    public static function canView($record): bool
    {
        return self::isPlatformUser();
    }

    public static function canDelete($record): bool
    {
        return self::isPlatformUser();
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


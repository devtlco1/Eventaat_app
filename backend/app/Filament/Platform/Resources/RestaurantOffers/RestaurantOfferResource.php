<?php

namespace App\Filament\Platform\Resources\RestaurantOffers;

use App\Filament\Concerns\GrantsPlatformOperationsCrud;
use App\Filament\Platform\Resources\RestaurantOffers\Pages\CreateRestaurantOffer;
use App\Filament\Platform\Resources\RestaurantOffers\Pages\EditRestaurantOffer;
use App\Filament\Platform\Resources\RestaurantOffers\Pages\ListRestaurantOffers;
use App\Filament\Platform\Resources\RestaurantOffers\Pages\ViewRestaurantOffer;
use App\Filament\Platform\Resources\RestaurantOffers\Schemas\RestaurantOfferForm;
use App\Filament\Platform\Resources\RestaurantOffers\Schemas\RestaurantOfferInfolist;
use App\Filament\Platform\Resources\RestaurantOffers\Tables\RestaurantOffersTable;
use App\Models\RestaurantOffer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RestaurantOfferResource extends Resource
{
    use GrantsPlatformOperationsCrud;

    protected static ?string $model = RestaurantOffer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 19;

    protected static ?string $navigationLabel = 'Offers';

    protected static ?string $modelLabel = 'offer';

    protected static ?string $pluralModelLabel = 'offers';

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

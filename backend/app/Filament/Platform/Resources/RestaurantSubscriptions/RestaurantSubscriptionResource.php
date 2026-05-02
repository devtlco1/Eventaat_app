<?php

namespace App\Filament\Platform\Resources\RestaurantSubscriptions;

use App\Filament\Concerns\GrantsPlatformOperationsCrud;
use App\Filament\Platform\Resources\RestaurantSubscriptions\Pages\CreateRestaurantSubscription;
use App\Filament\Platform\Resources\RestaurantSubscriptions\Pages\EditRestaurantSubscription;
use App\Filament\Platform\Resources\RestaurantSubscriptions\Pages\ListRestaurantSubscriptions;
use App\Filament\Platform\Resources\RestaurantSubscriptions\Pages\ViewRestaurantSubscription;
use App\Filament\Platform\Resources\RestaurantSubscriptions\Schemas\RestaurantSubscriptionForm;
use App\Filament\Platform\Resources\RestaurantSubscriptions\Schemas\RestaurantSubscriptionInfolist;
use App\Filament\Platform\Resources\RestaurantSubscriptions\Tables\RestaurantSubscriptionsTable;
use App\Models\RestaurantSubscription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RestaurantSubscriptionResource extends Resource
{
    use GrantsPlatformOperationsCrud;

    protected static ?string $model = RestaurantSubscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 25;

    protected static ?string $navigationLabel = 'Restaurant subscriptions';

    protected static ?string $modelLabel = 'restaurant subscription';

    protected static ?string $pluralModelLabel = 'restaurant subscriptions';

    public static function form(Schema $schema): Schema
    {
        return RestaurantSubscriptionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RestaurantSubscriptionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantSubscriptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurantSubscriptions::route('/'),
            'create' => CreateRestaurantSubscription::route('/create'),
            'view' => ViewRestaurantSubscription::route('/{record}'),
            'edit' => EditRestaurantSubscription::route('/{record}/edit'),
        ];
    }
}

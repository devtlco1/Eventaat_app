<?php

namespace App\Filament\Platform\Resources\RestaurantInvoices;

use App\Filament\Concerns\GrantsPlatformOperationsCrud;
use App\Filament\Platform\Resources\RestaurantInvoices\Pages\CreateRestaurantInvoice;
use App\Filament\Platform\Resources\RestaurantInvoices\Pages\EditRestaurantInvoice;
use App\Filament\Platform\Resources\RestaurantInvoices\Pages\ListRestaurantInvoices;
use App\Filament\Platform\Resources\RestaurantInvoices\Pages\ViewRestaurantInvoice;
use App\Filament\Platform\Resources\RestaurantInvoices\Schemas\RestaurantInvoiceForm;
use App\Filament\Platform\Resources\RestaurantInvoices\Schemas\RestaurantInvoiceInfolist;
use App\Filament\Platform\Resources\RestaurantInvoices\Tables\RestaurantInvoicesTable;
use App\Models\RestaurantInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RestaurantInvoiceResource extends Resource
{
    use GrantsPlatformOperationsCrud;

    protected static ?string $model = RestaurantInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 26;

    protected static ?string $navigationLabel = 'Restaurant invoices';

    protected static ?string $modelLabel = 'restaurant invoice';

    protected static ?string $pluralModelLabel = 'restaurant invoices';

    public static function form(Schema $schema): Schema
    {
        return RestaurantInvoiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RestaurantInvoiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantInvoicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurantInvoices::route('/'),
            'create' => CreateRestaurantInvoice::route('/create'),
            'view' => ViewRestaurantInvoice::route('/{record}'),
            'edit' => EditRestaurantInvoice::route('/{record}/edit'),
        ];
    }
}

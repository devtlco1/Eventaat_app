<?php

namespace App\Filament\Platform\Resources\RestaurantInvoices\Pages;

use App\Filament\Platform\Resources\RestaurantInvoices\RestaurantInvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantInvoices extends ListRecords
{
    protected static string $resource = RestaurantInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Platform\Resources\RestaurantInvoices\Pages;

use App\Filament\Platform\Resources\RestaurantInvoices\RestaurantInvoiceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRestaurantInvoice extends ViewRecord
{
    protected static string $resource = RestaurantInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

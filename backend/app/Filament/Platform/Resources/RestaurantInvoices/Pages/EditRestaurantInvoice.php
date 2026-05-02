<?php

namespace App\Filament\Platform\Resources\RestaurantInvoices\Pages;

use App\Filament\Platform\Resources\RestaurantInvoices\RestaurantInvoiceResource;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantInvoice extends EditRecord
{
    protected static string $resource = RestaurantInvoiceResource::class;
}

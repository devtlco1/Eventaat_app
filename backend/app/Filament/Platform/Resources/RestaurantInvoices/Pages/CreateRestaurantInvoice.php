<?php

namespace App\Filament\Platform\Resources\RestaurantInvoices\Pages;

use App\Filament\Platform\Resources\RestaurantInvoices\RestaurantInvoiceResource;
use App\Services\Finance\RestaurantInvoiceService;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurantInvoice extends CreateRecord
{
    protected static string $resource = RestaurantInvoiceResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $num = trim((string) ($data['invoice_number'] ?? ''));
        if ($num === '') {
            $data['invoice_number'] = app(RestaurantInvoiceService::class)->generateUniqueInvoiceNumber();
        }

        return $data;
    }
}

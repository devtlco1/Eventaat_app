<?php

namespace App\Filament\Platform\Resources\OtpDeliveryAttempts\Pages;

use App\Filament\Platform\Resources\OtpDeliveryAttempts\OtpDeliveryAttemptResource;
use Filament\Resources\Pages\ListRecords;

class ListOtpDeliveryAttempts extends ListRecords
{
    protected static string $resource = OtpDeliveryAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

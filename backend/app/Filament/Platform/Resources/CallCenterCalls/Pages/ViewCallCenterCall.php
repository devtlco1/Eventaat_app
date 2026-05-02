<?php

namespace App\Filament\Platform\Resources\CallCenterCalls\Pages;

use App\Filament\Platform\Resources\CallCenterCalls\CallCenterCallResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCallCenterCall extends ViewRecord
{
    protected static string $resource = CallCenterCallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

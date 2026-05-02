<?php

namespace App\Filament\Platform\Resources\CallCenterCalls\Pages;

use App\Filament\Platform\Resources\CallCenterCalls\CallCenterCallResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCallCenterCalls extends ListRecords
{
    protected static string $resource = CallCenterCallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

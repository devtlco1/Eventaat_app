<?php

namespace App\Filament\Platform\Resources\CallCenterCalls\Pages;

use App\Filament\Platform\Resources\CallCenterCalls\CallCenterCallResource;
use Filament\Resources\Pages\EditRecord;

class EditCallCenterCall extends EditRecord
{
    protected static string $resource = CallCenterCallResource::class;
}

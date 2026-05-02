<?php

namespace App\Filament\Platform\Resources\Permissions\Pages;

use App\Filament\Platform\Resources\Permissions\PermissionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPermission extends ViewRecord
{
    protected static string $resource = PermissionResource::class;
}

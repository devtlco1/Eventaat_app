<?php

namespace App\Filament\Platform\Resources\NotificationTemplates\Pages;

use App\Filament\Platform\Resources\NotificationTemplates\NotificationTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNotificationTemplate extends ViewRecord
{
    protected static string $resource = NotificationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}


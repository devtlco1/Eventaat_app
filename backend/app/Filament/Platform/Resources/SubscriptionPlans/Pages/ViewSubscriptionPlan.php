<?php

namespace App\Filament\Platform\Resources\SubscriptionPlans\Pages;

use App\Filament\Platform\Resources\SubscriptionPlans\SubscriptionPlanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSubscriptionPlan extends ViewRecord
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

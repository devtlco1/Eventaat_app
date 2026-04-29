<?php

namespace App\Filament\Restaurant\Resources\RestaurantStaffAssignments\Pages;

use App\Filament\Restaurant\Resources\RestaurantStaffAssignments\RestaurantStaffAssignmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRestaurantStaffAssignment extends ViewRecord
{
    protected static string $resource = RestaurantStaffAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

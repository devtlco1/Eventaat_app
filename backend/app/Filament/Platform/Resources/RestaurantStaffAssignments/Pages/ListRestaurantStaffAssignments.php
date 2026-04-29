<?php

namespace App\Filament\Platform\Resources\RestaurantStaffAssignments\Pages;

use App\Filament\Platform\Resources\RestaurantStaffAssignments\RestaurantStaffAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantStaffAssignments extends ListRecords
{
    protected static string $resource = RestaurantStaffAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

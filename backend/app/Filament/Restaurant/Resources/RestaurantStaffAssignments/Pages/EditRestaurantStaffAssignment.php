<?php

namespace App\Filament\Restaurant\Resources\RestaurantStaffAssignments\Pages;

use App\Filament\Restaurant\Resources\RestaurantStaffAssignments\RestaurantStaffAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantStaffAssignment extends EditRecord
{
    protected static string $resource = RestaurantStaffAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

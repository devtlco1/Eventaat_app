<?php

namespace App\Filament\Platform\Resources\RestaurantEvents\Pages;

use App\Filament\Platform\Resources\RestaurantEvents\RestaurantEventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantEvent extends EditRecord
{
    protected static string $resource = RestaurantEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}


<?php

namespace App\Filament\Restaurant\Resources\RestaurantEvents\Pages;

use App\Filament\Restaurant\Resources\RestaurantEvents\RestaurantEventResource;
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


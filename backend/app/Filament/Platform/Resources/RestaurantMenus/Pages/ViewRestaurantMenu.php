<?php

namespace App\Filament\Platform\Resources\RestaurantMenus\Pages;

use App\Filament\Platform\Resources\RestaurantMenus\RestaurantMenuResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRestaurantMenu extends ViewRecord
{
    protected static string $resource = RestaurantMenuResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->getRecord()->loadMissing(['categories.items']);
    }
}

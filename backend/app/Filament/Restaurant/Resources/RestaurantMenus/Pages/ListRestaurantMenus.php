<?php

namespace App\Filament\Restaurant\Resources\RestaurantMenus\Pages;

use App\Filament\Restaurant\Resources\RestaurantMenus\RestaurantMenuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListRestaurantMenus extends ListRecords
{
    protected static string $resource = RestaurantMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add menu')
                ->icon(Heroicon::OutlinedPlus)
                ->visible(fn (): bool => RestaurantMenuResource::canCreate()),
        ];
    }
}

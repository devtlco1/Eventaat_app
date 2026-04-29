<?php

namespace App\Filament\Restaurant\Resources\Restaurants\Schemas;

use App\Enums\RestaurantStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RestaurantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('slug')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('status')
                    ->options(array_combine(
                        array_map(fn (RestaurantStatus $s) => $s->value, RestaurantStatus::cases()),
                        array_map(fn (RestaurantStatus $s) => $s->value, RestaurantStatus::cases()),
                    ))
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }
}

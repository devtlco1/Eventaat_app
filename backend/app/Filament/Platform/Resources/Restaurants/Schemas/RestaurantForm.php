<?php

namespace App\Filament\Platform\Resources\Restaurants\Schemas;

use App\Enums\RestaurantStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RestaurantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state) => $state ? Str::slug($state) : null),
                Select::make('status')
                    ->required()
                    ->options(array_combine(
                        array_map(fn (RestaurantStatus $s) => $s->value, RestaurantStatus::cases()),
                        array_map(fn (RestaurantStatus $s) => $s->value, RestaurantStatus::cases()),
                    ))
                    ->default(RestaurantStatus::Active->value),
            ]);
    }
}

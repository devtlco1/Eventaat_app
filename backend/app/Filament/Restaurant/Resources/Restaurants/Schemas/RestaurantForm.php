<?php

namespace App\Filament\Restaurant\Resources\Restaurants\Schemas;

use App\Enums\RestaurantStatus;
use App\Filament\Support\FilamentSchemaLayout;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantForm
{
    public static function configure(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)->components([
            Section::make('Details')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
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
                    ]),
                ]),
        ]);
    }
}

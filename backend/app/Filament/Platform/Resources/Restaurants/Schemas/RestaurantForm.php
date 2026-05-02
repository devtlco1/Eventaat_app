<?php

namespace App\Filament\Platform\Resources\Restaurants\Schemas;

use App\Enums\RestaurantStatus;
use App\Filament\Support\FilamentSchemaLayout;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

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
                    ]),
                ]),
        ]);
    }
}

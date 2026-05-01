<?php

namespace App\Filament\Restaurant\Resources\RestaurantTables\Schemas;

use App\Enums\TableStatus;
use App\Models\User;
use App\Support\RestaurantPanelScope;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantTableForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return $schema->components([
            Section::make('Details')
                ->schema([
                    Select::make('seating_area_id')
                        ->options(fn () => $user
                            ? RestaurantPanelScope::seatingAreas($user)->orderBy('name')->pluck('name', 'id')->all()
                            : [])
                        ->required()
                        ->searchable(),
                    Grid::make(3)->schema([
                        TextInput::make('label')
                            ->required()
                            ->maxLength(64),
                        TextInput::make('capacity')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(99)
                            ->default(2),
                        Select::make('status')
                            ->required()
                            ->options(array_combine(
                                array_map(fn (TableStatus $s) => $s->value, TableStatus::cases()),
                                array_map(fn (TableStatus $s) => $s->value, TableStatus::cases()),
                            ))
                            ->default(TableStatus::Active->value),
                    ]),
                ]),
        ]);
    }
}

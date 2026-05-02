<?php

namespace App\Filament\Platform\Resources\SeatingAreas\Schemas;

use App\Enums\SeatingAreaType;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\SeatingArea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SeatingAreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Details')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->options(fn () => Restaurant::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->reactive()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Select $component, $state, $record): void {
                                /** @var SeatingArea|null $record */
                                if (! $record) {
                                    return;
                                }

                                $restaurantId = $record->branch?->restaurant_id;
                                if ($restaurantId) {
                                    $component->state($restaurantId);
                                }
                            }),
                        Select::make('branch_id')
                            ->label('Branch')
                            ->options(function (Get $get) {
                                $restaurantId = $get('restaurant_id');
                                if (! $restaurantId) {
                                    return [];
                                }

                                return Branch::query()
                                    ->where('restaurant_id', $restaurantId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->required()
                            ->reactive()
                            ->searchable(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('code')
                            ->required()
                            ->maxLength(64),
                        Select::make('type')
                            ->required()
                            ->options(array_combine(
                                array_map(fn (SeatingAreaType $t) => $t->value, SeatingAreaType::cases()),
                                array_map(fn (SeatingAreaType $t) => $t->value, SeatingAreaType::cases()),
                            ))
                            ->default(SeatingAreaType::Indoor->value),
                        Select::make('status')
                            ->required()
                            ->options(['active' => 'active', 'inactive' => 'inactive'])
                            ->default('active'),
                    ]),
                ]),
        ]);
    }
}

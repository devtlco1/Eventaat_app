<?php

namespace App\Filament\Platform\Resources\SeatingAreas\Schemas;

use App\Enums\SeatingAreaType;
use App\Models\Branch;
use App\Models\Restaurant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SeatingAreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('restaurant_id')
                    ->label('Restaurant')
                    ->options(fn () => Restaurant::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->reactive()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Select $component, $state, $record): void {
                        /** @var \App\Models\SeatingArea|null $record */
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
            ]);
    }
}

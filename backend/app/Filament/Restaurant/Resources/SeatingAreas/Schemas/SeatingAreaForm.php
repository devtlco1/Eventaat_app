<?php

namespace App\Filament\Restaurant\Resources\SeatingAreas\Schemas;

use App\Enums\SeatingAreaType;
use App\Filament\Support\FilamentSchemaLayout;
use App\Models\User;
use App\Support\RestaurantPanelScope;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SeatingAreaForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return FilamentSchemaLayout::stackSections($schema)->components([
            Section::make('Details')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('branch_id')
                            ->options(fn () => $user
                                ? RestaurantPanelScope::branches($user)->orderBy('name')->pluck('name', 'id')->all()
                                : [])
                            ->required()
                            ->searchable(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->required()
                            ->maxLength(64),
                    ]),
                    Grid::make(3)->schema([
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

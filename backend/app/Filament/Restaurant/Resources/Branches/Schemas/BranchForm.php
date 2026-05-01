<?php

namespace App\Filament\Restaurant\Resources\Branches\Schemas;

use App\Enums\BranchStatus;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        $restaurantIds = $user?->scopedRestaurantIds() ?? [];

        return $schema->components([
            Section::make('Details')
                ->schema([
                    Select::make('restaurant_id')
                        ->relationship(
                            name: 'restaurant',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->whereIn('id', $restaurantIds),
                        )
                        ->required()
                        ->searchable()
                        ->disabled(count($restaurantIds) === 1)
                        ->dehydrated(true),
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->required()
                            ->maxLength(64),
                    ]),
                    Select::make('status')
                        ->required()
                        ->options(array_combine(
                            array_map(fn (BranchStatus $s) => $s->value, BranchStatus::cases()),
                            array_map(fn (BranchStatus $s) => $s->value, BranchStatus::cases()),
                        ))
                        ->default(BranchStatus::Active->value),
                ]),
        ]);
    }
}

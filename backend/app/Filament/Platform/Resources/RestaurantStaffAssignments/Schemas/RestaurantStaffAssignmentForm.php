<?php

namespace App\Filament\Platform\Resources\RestaurantStaffAssignments\Schemas;

use App\Enums\RestaurantStaffRole;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class RestaurantStaffAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'email')
                    ->required()
                    ->searchable(),
                Select::make('restaurant_id')
                    ->relationship('restaurant', 'name')
                    ->required()
                    ->searchable(),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->nullable(),
                Select::make('role')
                    ->required()
                    ->options(array_combine(
                        array_map(fn (RestaurantStaffRole $r) => $r->value, RestaurantStaffRole::cases()),
                        array_map(fn (RestaurantStaffRole $r) => $r->value, RestaurantStaffRole::cases()),
                    )),
                Select::make('status')
                    ->required()
                    ->options(['active' => 'active', 'inactive' => 'inactive'])
                    ->default('active'),
            ]);
    }
}

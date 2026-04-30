<?php

namespace App\Filament\Platform\Resources\RestaurantStaffAssignments\Schemas;

use App\Enums\RestaurantStaffRole;
use App\Models\Branch;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

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
                    ->searchable()
                    ->reactive(),
                Select::make('branch_id')
                    ->label('Branch (optional)')
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
                    ->nullable()
                    ->searchable()
                    ->rule(function (Get $get) {
                        $restaurantId = $get('restaurant_id');

                        return $restaurantId
                            ? Rule::exists(Branch::class, 'id')->where('restaurant_id', $restaurantId)
                            : Rule::prohibitedIf(true);
                    }),
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

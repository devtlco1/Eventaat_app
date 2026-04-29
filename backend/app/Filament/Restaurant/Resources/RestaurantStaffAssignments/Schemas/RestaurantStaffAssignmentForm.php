<?php

namespace App\Filament\Restaurant\Resources\RestaurantStaffAssignments\Schemas;

use App\Enums\RestaurantStaffRole;
use App\Models\Branch;
use App\Support\RestaurantPanelScope;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class RestaurantStaffAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        $restaurantOptions = $user
            ? RestaurantPanelScope::restaurants($user)->orderBy('name')->pluck('name', 'id')->all()
            : [];

        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'email')
                    ->required()
                    ->searchable(),
                Select::make('restaurant_id')
                    ->options($restaurantOptions)
                    ->required()
                    ->reactive(),
                Select::make('branch_id')
                    ->label('Branch (optional)')
                    ->options(function (Get $get) use ($user) {
                        $restaurantId = $get('restaurant_id');
                        if (! $restaurantId || ! $user) {
                            return [];
                        }

                        // Branch options are always limited to the selected restaurant,
                        // and also to the current user's restaurant-panel scope.
                        return RestaurantPanelScope::branches($user)
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

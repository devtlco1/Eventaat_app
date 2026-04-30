<?php

namespace App\Filament\Platform\Resources\Bookings\Schemas;

use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Enums\TableStatus;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\SeatingArea;
use App\Models\User;
use App\Services\Otp\MobileOtpService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ManualBookingCreateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('customer_lookup_message')
                    ->dehydrated(false)
                    ->hidden(),
                TextInput::make('customer_exists')
                    ->dehydrated(false)
                    ->hidden(),
                TextInput::make('customer_phone')
                    ->label('Customer phone')
                    ->required()
                    ->debounce(600)
                    ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                        $normalized = MobileOtpService::normalizePhone((string) $state);
                        if ($normalized === '') {
                            $set('customer_exists', false);
                            $set('customer_lookup_message', null);
                            return;
                        }

                        /** @var User|null $user */
                        $user = User::query()->where('phone', $normalized)->first();

                        if ($user) {
                            $set('customer_exists', true);
                            $set('customer_lookup_message', 'Existing customer found');

                            $currentName = trim((string) ($get('customer_name') ?? ''));
                            $userName = trim((string) $user->name);
                            if ($currentName === '' && $userName !== '' && Str::lower($userName) !== 'customer') {
                                $set('customer_name', $userName);
                            }

                            return;
                        }

                        $set('customer_exists', false);
                        $set('customer_lookup_message', 'New customer will be created');
                    })
                    ->helperText(fn (Get $get) => $get('customer_lookup_message') ?: null)
                    ->maxLength(32),
                TextInput::make('customer_name')
                    ->label('Customer name')
                    ->required(fn (Get $get) => ! (bool) $get('customer_exists'))
                    ->maxLength(255),

                Select::make('restaurant_id')
                    ->label('Restaurant')
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->options(fn () => Restaurant::query()
                        ->where('status', RestaurantStatus::Active->value)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),

                Select::make('branch_id')
                    ->label('Branch')
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->options(function (Get $get) {
                        $restaurantId = $get('restaurant_id');
                        if (! $restaurantId) {
                            return [];
                        }

                        return Branch::query()
                            ->where('restaurant_id', (int) $restaurantId)
                            ->where('status', BranchStatus::Active->value)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    }),

                Select::make('seating_area_id')
                    ->label('Seating Area (optional)')
                    ->nullable()
                    ->searchable()
                    ->reactive()
                    ->options(function (Get $get) {
                        $branchId = $get('branch_id');
                        if (! $branchId) {
                            return [];
                        }

                        return SeatingArea::query()
                            ->where('branch_id', (int) $branchId)
                            ->where('status', 'active')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    }),

                Select::make('restaurant_table_id')
                    ->label('Table (optional)')
                    ->nullable()
                    ->searchable()
                    ->options(function (Get $get) {
                        $branchId = $get('branch_id');
                        if (! $branchId) {
                            return [];
                        }

                        $seatingAreaId = $get('seating_area_id');

                        $query = RestaurantTable::query()
                            ->where('status', TableStatus::Active->value)
                            ->whereHas('seatingArea', function ($q) use ($branchId, $seatingAreaId) {
                                $q->where('branch_id', (int) $branchId)
                                    ->where('status', 'active');

                                if ($seatingAreaId) {
                                    $q->whereKey((int) $seatingAreaId);
                                }
                            })
                            ->orderBy('label');

                        return $query
                            ->get()
                            ->mapWithKeys(fn (RestaurantTable $t) => [$t->id => "{$t->label} (cap {$t->capacity})"])
                            ->all();
                    }),

                DateTimePicker::make('starts_at')
                    ->label('Starts at')
                    ->required()
                    ->format('Y-m-d H:i')
                    ->seconds(false)
                    ->minutesStep(1)
                    ->minDate(Carbon::now()->addMinute()->startOfMinute()),

                TextInput::make('party_size')
                    ->label('Party size')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(100)
                    ->default(2),

                Textarea::make('customer_note')
                    ->label('Customer note (optional)')
                    ->maxLength(2000)
                    ->columnSpanFull(),

                Textarea::make('restaurant_note')
                    ->label('Restaurant note (optional)')
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }
}


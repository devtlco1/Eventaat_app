<?php

namespace App\Filament\Restaurant\Resources\Bookings\Schemas;

use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Enums\TableStatus;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantEvent;
use App\Models\RestaurantTable;
use App\Models\SeatingArea;
use App\Models\User;
use App\Services\Otp\MobileOtpService;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ManualBookingCreateForm
{
    private static function seatingAreaOptionLabel(SeatingArea $area): string
    {
        $parts = [$area->name];
        if (filled($area->code)) {
            $parts[] = '('.$area->code.')';
        }
        $type = $area->type;
        if ($type instanceof \BackedEnum) {
            $parts[] = '['.$type->value.']';
        } elseif (filled($type)) {
            $parts[] = '['.(string) $type.']';
        }

        return implode(' ', $parts);
    }

    public static function configure(Schema $schema): Schema
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();
        $scopedRestaurantIds = $user?->scopedRestaurantIds() ?? [];
        $scopedBranchIds = $user?->scopedBranchIds() ?? [];

        $singleRestaurantId = count($scopedRestaurantIds) === 1 ? (int) $scopedRestaurantIds[0] : null;
        $singleBranchId = count($scopedBranchIds) === 1 ? (int) $scopedBranchIds[0] : null;

        return $schema->components([
            TextInput::make('customer_lookup_message')
                ->dehydrated(false)
                ->hidden(),
            TextInput::make('customer_exists')
                ->dehydrated(false)
                ->hidden(),

            Section::make('Customer')
                ->compact()
                ->schema([
                    Grid::make(2)->schema([
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
                    ]),
                ]),

            Section::make('Where & when')
                ->compact()
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('branch_id', null);
                                $set('seating_area_id', null);
                                $set('restaurant_table_id', null);
                            })
                            ->default($singleRestaurantId)
                            ->hidden(fn () => $singleRestaurantId !== null)
                            ->dehydrated(true)
                            ->options(function () use ($scopedRestaurantIds) {
                                $query = Restaurant::query()
                                    ->where('status', RestaurantStatus::Active->value)
                                    ->orderBy('name');

                                if ($scopedRestaurantIds !== []) {
                                    $query->whereIn('id', $scopedRestaurantIds);
                                }

                                return $query->pluck('name', 'id')->all();
                            }),

                        Select::make('branch_id')
                            ->label('Branch')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('restaurant_event_id', null);
                                $set('seating_area_id', null);
                                $set('restaurant_table_id', null);
                            })
                            ->default($singleBranchId)
                            ->hidden(fn () => $singleBranchId !== null)
                            ->dehydrated(true)
                            ->options(function (Get $get) use ($scopedBranchIds) {
                                $restaurantId = $get('restaurant_id');
                                if (! $restaurantId) {
                                    return [];
                                }

                                $query = Branch::query()
                                    ->where('restaurant_id', (int) $restaurantId)
                                    ->where('status', BranchStatus::Active->value)
                                    ->orderBy('name');

                                if ($scopedBranchIds !== []) {
                                    $query->whereIn('id', $scopedBranchIds);
                                }

                                return $query->pluck('name', 'id')->all();
                            }),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('restaurant_event_id')
                            ->label('Event (optional)')
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->helperText(function (Get $get): ?string {
                                $eventId = $get('restaurant_event_id');
                                if (! $eventId) {
                                    return 'Optional: link this booking to a published event night.';
                                }

                                /** @var RestaurantEvent|null $event */
                                $event = RestaurantEvent::query()->find((int) $eventId);
                                if (! $event) {
                                    return null;
                                }

                                $starts = $event->starts_at?->format('Y-m-d H:i');
                                $ends = $event->ends_at?->format('Y-m-d H:i');

                                return $ends
                                    ? "Event time: {$starts} → {$ends}"
                                    : ($starts ? "Event time: {$starts}" : null);
                            })
                            ->options(function (Get $get) use ($scopedBranchIds) {
                                $restaurantId = $get('restaurant_id');
                                $branchId = $get('branch_id');
                                if (! $restaurantId || ! $branchId) {
                                    return [];
                                }

                                $branchId = (int) $branchId;

                                $query = RestaurantEvent::query()
                                    ->where('restaurant_id', (int) $restaurantId)
                                    ->where('status', RestaurantEvent::STATUS_PUBLISHED)
                                    ->whereIn('booking_mode', [RestaurantEvent::BOOKING_MODE_NORMAL, RestaurantEvent::BOOKING_MODE_EVENT]);

                                if ($scopedBranchIds !== []) {
                                    $query->where('branch_id', $branchId);
                                } else {
                                    $query->where(function ($q) use ($branchId) {
                                        $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
                                    });
                                }

                                return $query
                                    ->orderByDesc('starts_at')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function (RestaurantEvent $e) {
                                        $when = $e->starts_at?->format('Y-m-d H:i') ?? '';

                                        return [$e->id => "{$e->title} — {$when}"];
                                    })
                                    ->all();
                            }),

                        Select::make('seating_area_id')
                            ->label('Seating Area (optional)')
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->helperText(fn (Get $get): ?string => $get('branch_id') ? null : 'Select a branch first.')
                            ->afterStateUpdated(function (Set $set): void {
                                $set('restaurant_table_id', null);
                            })
                            ->options(function (Get $get) {
                                $branchId = $get('branch_id');
                                if (! $branchId) {
                                    return [];
                                }

                                return SeatingArea::query()
                                    ->where('branch_id', (int) $branchId)
                                    ->where('status', 'active')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (SeatingArea $a) => [$a->id => self::seatingAreaOptionLabel($a)])
                                    ->all();
                            }),
                    ]),

                    Select::make('restaurant_table_id')
                        ->label('Table (optional)')
                        ->nullable()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->helperText(function (Get $get): ?string {
                            if (! $get('branch_id')) {
                                return 'Select a branch first.';
                            }
                            if (! $get('seating_area_id')) {
                                return 'Active tables for this branch (all seating areas). Choose a seating area above to narrow the list.';
                            }

                            return null;
                        })
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

                    Grid::make(2)->schema([
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
                    ]),
                ]),

            Section::make('Notes')
                ->compact()
                ->collapsed()
                ->schema([
                    Textarea::make('customer_note')
                        ->label('Customer note (optional)')
                        ->maxLength(2000)
                        ->columnSpanFull(),
                    Textarea::make('restaurant_note')
                        ->label('Restaurant note (optional)')
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

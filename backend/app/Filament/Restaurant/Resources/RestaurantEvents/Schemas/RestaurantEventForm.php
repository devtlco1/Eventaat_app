<?php

namespace App\Filament\Restaurant\Resources\RestaurantEvents\Schemas;

use App\Models\Branch;
use App\Models\RestaurantEvent;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RestaurantEventForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        $restaurantIds = $user?->scopedRestaurantIds() ?? [];
        $branchIds = $user?->scopedBranchIds() ?? [];

        $isBranchScoped = count($branchIds) > 0;

        return $schema->components([
            Section::make('Basics')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (?string $state) => $state ? Str::slug($state) : null),
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->relationship(
                                name: 'restaurant',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereIn('id', $restaurantIds),
                            )
                            ->required()
                            ->searchable()
                            ->disabled(count($restaurantIds) === 1)
                            ->dehydrated(true)
                            ->reactive(),
                    ]),
                    Grid::make(3)->schema([
                        Select::make('branch_id')
                            ->label('Branch'.($isBranchScoped ? '' : ' (optional)'))
                            ->options(function (Get $get) use ($branchIds) {
                                $restaurantId = $get('restaurant_id');
                                if (! $restaurantId) {
                                    return [];
                                }

                                $query = Branch::query()
                                    ->where('restaurant_id', $restaurantId)
                                    ->orderBy('name');

                                if (count($branchIds)) {
                                    $query->whereIn('id', $branchIds);
                                }

                                return $query->pluck('name', 'id')->all();
                            })
                            ->searchable()
                            ->required($isBranchScoped)
                            ->nullable(! $isBranchScoped)
                            ->rule(function (Get $get) use ($branchIds) {
                                $restaurantId = $get('restaurant_id');

                                if (! $restaurantId) {
                                    return Rule::prohibitedIf(true);
                                }

                                $rule = Rule::exists(Branch::class, 'id')->where('restaurant_id', $restaurantId);

                                if (count($branchIds)) {
                                    $rule->whereIn('id', $branchIds);
                                }

                                return $rule;
                            }),
                    ]),
                ]),

            Section::make('Schedule & capacity')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('status')
                            ->required()
                            ->options(array_combine(RestaurantEvent::STATUSES, RestaurantEvent::STATUSES))
                            ->default(RestaurantEvent::STATUS_DRAFT),
                        Select::make('booking_mode')
                            ->required()
                            ->options(array_combine(RestaurantEvent::BOOKING_MODES, RestaurantEvent::BOOKING_MODES))
                            ->default(RestaurantEvent::BOOKING_MODE_INFO_ONLY),
                        TextInput::make('price_label')
                            ->maxLength(255)
                            ->nullable(),
                    ]),
                    Grid::make(2)->schema([
                        DateTimePicker::make('starts_at')
                            ->required(),
                        DateTimePicker::make('ends_at')
                            ->nullable()
                            ->rule('after:starts_at'),
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('capacity')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),
                        Placeholder::make('active_reserved_seats')
                            ->label('Active reserved seats')
                            ->content(fn (?RestaurantEvent $record) => $record ? (string) $record->activeReservedSeats() : '—'),
                        Placeholder::make('remaining_seats')
                            ->label('Remaining seats')
                            ->content(function (?RestaurantEvent $record) {
                                if (! $record) {
                                    return '—';
                                }

                                return $record->capacity === null ? 'Unlimited' : (string) $record->remainingSeats();
                            }),
                    ]),
                ]),

            Section::make('Description & notes')
                ->compact()
                ->collapsed()
                ->schema([
                    Textarea::make('description')
                        ->rows(5)
                        ->nullable()
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

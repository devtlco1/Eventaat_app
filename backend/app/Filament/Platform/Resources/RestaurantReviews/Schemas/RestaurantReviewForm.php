<?php

namespace App\Filament\Platform\Resources\RestaurantReviews\Schemas;

use App\Filament\Support\FilamentSchemaLayout;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantReview;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class RestaurantReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)->components([
            Section::make('Review')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->options(fn () => Restaurant::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('branch_id', null);
                                $set('booking_id', null);
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
                            ->searchable()
                            ->nullable()
                            ->reactive()
                            ->afterStateUpdated(fn (Set $set) => $set('booking_id', null))
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, $fail) use ($get): void {
                                    if (! $value) {
                                        return;
                                    }

                                    $restaurantId = $get('restaurant_id');
                                    if (! $restaurantId) {
                                        $fail('Select a restaurant first.');

                                        return;
                                    }

                                    $ok = Branch::query()
                                        ->whereKey($value)
                                        ->where('restaurant_id', $restaurantId)
                                        ->exists();

                                    if (! $ok) {
                                        $fail('The selected branch does not belong to the selected restaurant.');
                                    }
                                },
                            ]),
                        Select::make('rating')
                            ->label('Rating')
                            ->options([
                                1 => '1 - Poor',
                                2 => '2 - Fair',
                                3 => '3 - Good',
                                4 => '4 - Very good',
                                5 => '5 - Excellent',
                            ])
                            ->required(),
                    ]),
                    Grid::make(3)->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(array_combine(RestaurantReview::STATUSES, RestaurantReview::STATUSES))
                            ->default(RestaurantReview::STATUS_PENDING_REVIEW)
                            ->required(),
                        Select::make('source')
                            ->label('Source')
                            ->options(array_combine(RestaurantReview::SOURCES, RestaurantReview::SOURCES))
                            ->default(RestaurantReview::SOURCE_DASHBOARD)
                            ->required(),
                        Select::make('booking_id')
                            ->label('Booking (optional)')
                            ->options(function (Get $get) {
                                $restaurantId = $get('restaurant_id');
                                if (! $restaurantId) {
                                    return [];
                                }

                                $query = Booking::query()
                                    ->where('restaurant_id', $restaurantId);

                                $branchId = $get('branch_id');
                                if ($branchId) {
                                    $query->where('branch_id', $branchId);
                                }

                                return $query->orderByDesc('id')
                                    ->limit(50)
                                    ->pluck('id', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->nullable()
                            ->rules([
                                fn (Get $get) => function (string $attribute, $value, $fail) use ($get): void {
                                    if (! $value) {
                                        return;
                                    }

                                    $restaurantId = $get('restaurant_id');
                                    $branchId = $get('branch_id');

                                    /** @var Booking|null $booking */
                                    $booking = Booking::query()->find($value);

                                    if (! $booking) {
                                        $fail('The selected booking could not be found.');

                                        return;
                                    }

                                    if ((int) $booking->restaurant_id !== (int) $restaurantId) {
                                        $fail('The selected booking does not belong to the selected restaurant.');

                                        return;
                                    }

                                    if ($branchId && (int) $booking->branch_id !== (int) $branchId) {
                                        $fail('The selected booking does not belong to the selected branch.');
                                    }
                                },
                            ]),
                    ]),
                    Textarea::make('comment')
                        ->label('Comment')
                        ->rows(4)
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            Section::make('Customer details')
                ->compact()
                ->collapsed()
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('customer_name')
                            ->label('Customer name')
                            ->maxLength(255)
                            ->nullable(),
                        TextInput::make('customer_phone')
                            ->label('Customer phone')
                            ->maxLength(32)
                            ->nullable(),
                        Select::make('user_id')
                            ->label('Linked user (optional)')
                            ->options(fn () => User::query()->orderBy('name')->limit(50)->pluck('name', 'id')->all())
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) => User::query()
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all())
                            ->nullable(),
                    ]),
                ]),

            Section::make('Admin notes')
                ->compact()
                ->collapsed()
                ->schema([
                    Textarea::make('admin_notes')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

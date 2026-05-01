<?php

namespace App\Filament\Platform\Resources\SupportTickets\Schemas;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\SupportTicket;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ticket')
                ->schema([
                    TextInput::make('subject')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Grid::make(2)->schema([
                        Select::make('category')
                            ->required()
                            ->options(array_combine(SupportTicket::CATEGORIES, SupportTicket::CATEGORIES))
                            ->default(SupportTicket::CATEGORY_GENERAL),
                        Select::make('priority')
                            ->required()
                            ->options(array_combine(SupportTicket::PRIORITIES, SupportTicket::PRIORITIES))
                            ->default(SupportTicket::PRIORITY_NORMAL),
                    ]),
                    Grid::make(2)->schema([
                        Select::make('status')
                            ->required()
                            ->options(array_combine(SupportTicket::STATUSES, SupportTicket::STATUSES))
                            ->default(SupportTicket::STATUS_OPEN),
                        Select::make('source')
                            ->required()
                            ->options(array_combine(SupportTicket::SOURCES, SupportTicket::SOURCES))
                            ->default(SupportTicket::SOURCE_DASHBOARD),
                    ]),
                ]),

            Section::make('Restaurant & booking')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->options(fn () => Restaurant::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->nullable()
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
                                        $fail('Select a restaurant before choosing a branch.');

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
                    ]),

                    Select::make('booking_id')
                        ->label('Booking')
                        ->options(function (Get $get) {
                            $restaurantId = $get('restaurant_id');
                            if (! $restaurantId) {
                                return [];
                            }

                            $query = Booking::query()
                                ->where('restaurant_id', $restaurantId)
                                ->orderByDesc('id')
                                ->limit(100);

                            $branchId = $get('branch_id');
                            if ($branchId) {
                                $query->where('branch_id', $branchId);
                            }

                            return $query->pluck('id', 'id')->all();
                        })
                        ->searchable()
                        ->nullable()
                        ->rules([
                            fn (Get $get) => function (string $attribute, $value, $fail) use ($get): void {
                                if (! $value) {
                                    return;
                                }

                                if (! $get('restaurant_id')) {
                                    $fail('Select a restaurant before choosing a booking.');

                                    return;
                                }

                                /** @var Booking|null $booking */
                                $booking = Booking::query()->find($value);

                                if (! $booking) {
                                    $fail('The selected booking could not be found.');

                                    return;
                                }

                                if ((int) $booking->restaurant_id !== (int) $get('restaurant_id')) {
                                    $fail('The selected booking does not belong to the selected restaurant.');
                                }

                                $branchId = $get('branch_id');
                                if ($branchId && (int) $booking->branch_id !== (int) $branchId) {
                                    $fail('The selected booking does not belong to the selected branch.');
                                }
                            },
                        ]),
                ]),

            Section::make('Customer')
                ->collapsed()
                ->schema([
                    Select::make('user_id')
                        ->label('Linked user')
                        ->relationship(name: 'user', titleAttribute: 'name')
                        ->searchable()
                        ->nullable()
                        ->preload(),
                    Grid::make(2)->schema([
                        TextInput::make('customer_name')
                            ->label('Customer name')
                            ->maxLength(255)
                            ->nullable(),
                        TextInput::make('customer_phone')
                            ->label('Customer phone')
                            ->maxLength(32)
                            ->nullable(),
                    ]),
                ]),

            Section::make('Content')
                ->schema([
                    Textarea::make('message')
                        ->rows(5)
                        ->nullable()
                        ->columnSpanFull(),
                    Textarea::make('internal_notes')
                        ->label('Internal notes')
                        ->rows(4)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

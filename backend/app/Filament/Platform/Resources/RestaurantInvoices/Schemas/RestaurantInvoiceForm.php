<?php

namespace App\Filament\Platform\Resources\RestaurantInvoices\Schemas;

use App\Enums\RestaurantInvoiceStatus;
use App\Models\RestaurantInvoice;
use App\Models\RestaurantSubscription;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class RestaurantInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Invoice')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->relationship('restaurant', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('restaurant_subscription_id', null)),
                        Select::make('restaurant_subscription_id')
                            ->label('Subscription')
                            ->options(function (Get $get): array {
                                $rid = $get('restaurant_id');
                                if (! $rid) {
                                    return [];
                                }

                                return RestaurantSubscription::query()
                                    ->where('restaurant_id', $rid)
                                    ->with('subscriptionPlan')
                                    ->orderByDesc('id')
                                    ->get()
                                    ->mapWithKeys(function (RestaurantSubscription $s): array {
                                        $plan = $s->subscriptionPlan?->name ?? '—';

                                        return [$s->id => '#'.$s->id.' — '.$plan.' ('.$s->status->value.')'];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->nullable(),
                        TextInput::make('invoice_number')
                            ->label('Invoice number')
                            ->maxLength(64)
                            ->unique(RestaurantInvoice::class, ignoreRecord: true)
                            ->helperText(__('Leave empty to auto-generate (INV-YEAR-000001).')),
                    ]),
                    Grid::make(3)->schema([
                        Select::make('status')
                            ->required()
                            ->options(collect(RestaurantInvoiceStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
                        DatePicker::make('issue_date')->native(false)->nullable(),
                        DatePicker::make('due_date')->native(false)->nullable(),
                    ]),
                    Grid::make(3)->schema([
                        DateTimePicker::make('paid_at')->nullable()->seconds(false),
                    ]),
                ]),
            Section::make('Amounts')
                ->compact()
                ->description(__('Total is recalculated as subtotal − discount + tax when saving.'))
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('subtotal_amount')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01)
                            ->required(),
                        TextInput::make('discount_amount')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01)
                            ->required(),
                        TextInput::make('tax_amount')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01)
                            ->required(),
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('total_amount')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0),
                        TextInput::make('currency')
                            ->required()
                            ->maxLength(8)
                            ->default('IQD'),
                    ]),
                ]),
            Section::make('Notes')
                ->compact()
                ->collapsed()
                ->schema([
                    Textarea::make('notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

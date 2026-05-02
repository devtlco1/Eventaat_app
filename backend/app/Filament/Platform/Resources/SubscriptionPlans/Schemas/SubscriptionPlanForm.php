<?php

namespace App\Filament\Platform\Resources\SubscriptionPlans\Schemas;

use App\Enums\SubscriptionBillingInterval;
use App\Models\SubscriptionPlan;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SubscriptionPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Plan')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set, $get) => $get('slug') ? null : $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(SubscriptionPlan::class, ignoreRecord: true)
                            ->alphaDash(),
                    ]),
                    Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                    Grid::make(3)->schema([
                        TextInput::make('price_amount')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix(fn ($get) => $get('currency') ?: 'IQD'),
                        TextInput::make('currency')
                            ->required()
                            ->maxLength(8)
                            ->default('IQD'),
                        Select::make('billing_interval')
                            ->required()
                            ->options(collect(SubscriptionBillingInterval::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
                    ]),
                    Grid::make(2)->schema([
                        Toggle::make('is_active')
                            ->default(true),
                        TextInput::make('display_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ]),
                ]),
        ]);
    }
}

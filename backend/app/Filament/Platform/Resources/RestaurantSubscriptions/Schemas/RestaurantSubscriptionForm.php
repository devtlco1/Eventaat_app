<?php

namespace App\Filament\Platform\Resources\RestaurantSubscriptions\Schemas;

use App\Enums\RestaurantSubscriptionStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantSubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Subscription')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->relationship('restaurant', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('subscription_plan_id')
                            ->label('Plan')
                            ->relationship(
                                'subscriptionPlan',
                                'name',
                                modifyQueryUsing: fn ($query) => $query->orderBy('display_order')->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]),
                    Select::make('status')
                        ->required()
                        ->options(collect(RestaurantSubscriptionStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
                    Grid::make(3)->schema([
                        DateTimePicker::make('starts_at')->nullable()->seconds(false),
                        DateTimePicker::make('ends_at')->nullable()->seconds(false),
                        DateTimePicker::make('cancelled_at')->nullable()->seconds(false),
                    ]),
                    Textarea::make('notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

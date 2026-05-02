<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class BranchAvailabilityRuleFormComponents
{
    /**
     * @return array<int, Section>
     */
    public static function make(): array
    {
        return [
            Section::make()
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        Toggle::make('is_booking_enabled')
                            ->label('Booking enabled')
                            ->default(true)
                            ->required(),
                        TextInput::make('booking_duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->minValue(15)
                            ->maxValue(720)
                            ->default(90)
                            ->required(),
                        TextInput::make('min_advance_minutes')
                            ->label('Min advance (minutes)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(60 * 24 * 60)
                            ->default(60)
                            ->required(),
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('max_advance_days')
                            ->label('Max advance (days)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(365)
                            ->default(30)
                            ->required(),
                        TimePicker::make('open_time')
                            ->label('Opens at')
                            ->seconds(false)
                            ->native(false),
                        TimePicker::make('close_time')
                            ->label('Closes at')
                            ->seconds(false)
                            ->native(false),
                    ]),
                    Grid::make(4)->schema([
                        Toggle::make('mon')->label('Mon')->default(true)->inline(false)->required(),
                        Toggle::make('tue')->label('Tue')->default(true)->inline(false)->required(),
                        Toggle::make('wed')->label('Wed')->default(true)->inline(false)->required(),
                        Toggle::make('thu')->label('Thu')->default(true)->inline(false)->required(),
                    ]),
                    Grid::make(3)->schema([
                        Toggle::make('fri')->label('Fri')->default(true)->inline(false)->required(),
                        Toggle::make('sat')->label('Sat')->default(true)->inline(false)->required(),
                        Toggle::make('sun')->label('Sun')->default(true)->inline(false)->required(),
                    ]),
                    Textarea::make('notes')->label('Notes')->rows(3)->maxLength(2000)->columnSpanFull(),
                ]),
        ];
    }
}

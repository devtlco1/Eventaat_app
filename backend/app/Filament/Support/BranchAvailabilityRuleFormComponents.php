<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;

class BranchAvailabilityRuleFormComponents
{
    /**
     * @return array<int, Component>
     */
    public static function make(): array
    {
        return [
            Toggle::make('is_booking_enabled')
                ->label('Booking enabled')
                ->default(true)
                ->required(),
            TextInput::make('booking_duration_minutes')
                ->label('Booking duration (minutes)')
                ->numeric()
                ->minValue(15)
                ->maxValue(720)
                ->default(90)
                ->required(),
            TextInput::make('min_advance_minutes')
                ->label('Minimum advance (minutes)')
                ->numeric()
                ->minValue(0)
                ->maxValue(60 * 24 * 60)
                ->default(60)
                ->required(),
            TextInput::make('max_advance_days')
                ->label('Maximum advance (days)')
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
            Toggle::make('mon')->label('Monday')->default(true)->inline(false)->required(),
            Toggle::make('tue')->label('Tuesday')->default(true)->inline(false)->required(),
            Toggle::make('wed')->label('Wednesday')->default(true)->inline(false)->required(),
            Toggle::make('thu')->label('Thursday')->default(true)->inline(false)->required(),
            Toggle::make('fri')->label('Friday')->default(true)->inline(false)->required(),
            Toggle::make('sat')->label('Saturday')->default(true)->inline(false)->required(),
            Toggle::make('sun')->label('Sunday')->default(true)->inline(false)->required(),
            Textarea::make('notes')->label('Notes')->rows(3)->maxLength(2000)->columnSpanFull(),
        ];
    }
}

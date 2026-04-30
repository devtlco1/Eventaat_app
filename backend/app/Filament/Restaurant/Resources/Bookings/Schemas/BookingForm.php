<?php

namespace App\Filament\Restaurant\Resources\Bookings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('status')->disabled(),
                DateTimePicker::make('starts_at')->disabled(),
                TextInput::make('party_size')->numeric()->disabled(),

                Textarea::make('customer_note')->disabled()->columnSpanFull(),
                Textarea::make('restaurant_note')->columnSpanFull(),
            ]);
    }
}

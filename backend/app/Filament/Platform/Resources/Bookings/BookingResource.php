<?php

namespace App\Filament\Platform\Resources\Bookings;

use App\Filament\Concerns\GrantsPlatformOperationsCrud;
use App\Filament\Platform\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Platform\Resources\Bookings\Pages\EditBooking;
use App\Filament\Platform\Resources\Bookings\Pages\ListBookings;
use App\Filament\Platform\Resources\Bookings\Schemas\BookingForm;
use App\Filament\Platform\Resources\Bookings\Tables\BookingsTable;
use App\Filament\RelationManagers\BookingAuditLogsRelationManager;
use App\Models\Booking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    use GrantsPlatformOperationsCrud;

    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Bookings';

    public static function form(Schema $schema): Schema
    {
        return BookingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BookingAuditLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'create' => CreateBooking::route('/create'),
            'edit' => EditBooking::route('/{record}/edit'),
        ];
    }
}

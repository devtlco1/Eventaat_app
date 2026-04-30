<?php

namespace App\Filament\Restaurant\Resources\Bookings;

use App\Filament\Restaurant\Resources\Bookings\Pages\EditBooking;
use App\Filament\Restaurant\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Restaurant\Resources\Bookings\Pages\ListBookings;
use App\Filament\Restaurant\Resources\Bookings\Schemas\BookingForm;
use App\Filament\Restaurant\Resources\Bookings\Schemas\ManualBookingCreateForm;
use App\Filament\Restaurant\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use App\Support\RestaurantPanelScope;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        return RestaurantPanelScope::bookings($user);
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        return RestaurantPanelScope::bookings($user)->whereKey($record)->exists();
    }

    public static function form(Schema $schema): Schema
    {
        $routeName = request()->route()?->getName() ?? '';

        if (str_ends_with($routeName, '.create')) {
            return ManualBookingCreateForm::configure($schema);
        }

        return BookingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
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

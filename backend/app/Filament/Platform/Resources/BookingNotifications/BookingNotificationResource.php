<?php

namespace App\Filament\Platform\Resources\BookingNotifications;

use App\Filament\Platform\Resources\BookingNotifications\Pages\ListBookingNotifications;
use App\Filament\Platform\Resources\BookingNotifications\Tables\BookingNotificationsTable;
use App\Models\BookingNotification;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BookingNotificationResource extends Resource
{
    protected static ?string $model = BookingNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationLabel = 'Booking notifications';

    protected static ?string $modelLabel = 'booking notification';

    protected static ?string $pluralModelLabel = 'booking notifications';

    public static function shouldRegisterNavigation(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return (bool) $user?->hasAnyRole(['super_admin', 'operations_admin']);
    }

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return (bool) $user?->hasAnyRole(['super_admin', 'operations_admin']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return BookingNotificationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookingNotifications::route('/'),
        ];
    }
}

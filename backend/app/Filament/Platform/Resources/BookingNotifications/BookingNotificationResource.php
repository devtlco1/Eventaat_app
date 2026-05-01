<?php

namespace App\Filament\Platform\Resources\BookingNotifications;

use App\Filament\Platform\Resources\BookingNotifications\Pages\ListBookingNotifications;
use App\Filament\Platform\Resources\BookingNotifications\Pages\ViewBookingNotification;
use App\Filament\Platform\Resources\BookingNotifications\RelationManagers\DispatchAttemptsRelationManager;
use App\Filament\Platform\Resources\BookingNotifications\Tables\BookingNotificationsTable;
use App\Models\BookingNotification;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Infolists\Components\TextEntry;

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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('id'),
            TextEntry::make('booking_id')->label('Booking'),
            TextEntry::make('event')->badge(),
            TextEntry::make('status')->badge(),
            TextEntry::make('channel'),
            TextEntry::make('recipient_name')->label('Recipient'),
            TextEntry::make('recipient_phone')->label('Phone'),
            TextEntry::make('title'),
            TextEntry::make('message')->markdown()->columnSpanFull(),
            TextEntry::make('sent_at')->dateTime(),
            TextEntry::make('failed_at')->dateTime(),
            TextEntry::make('failure_reason')->markdown()->columnSpanFull(),
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('updated_at')->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return BookingNotificationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DispatchAttemptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookingNotifications::route('/'),
            'view' => ViewBookingNotification::route('/{record}'),
        ];
    }
}

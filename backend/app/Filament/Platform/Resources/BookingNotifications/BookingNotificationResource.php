<?php

namespace App\Filament\Platform\Resources\BookingNotifications;

use App\Filament\Concerns\AuthorizesPlatformOperations;
use App\Filament\Platform\Resources\BookingNotifications\Pages\ListBookingNotifications;
use App\Filament\Platform\Resources\BookingNotifications\Pages\ViewBookingNotification;
use App\Filament\Platform\Resources\BookingNotifications\RelationManagers\DispatchAttemptsRelationManager;
use App\Filament\Platform\Resources\BookingNotifications\Tables\BookingNotificationsTable;
use App\Models\BookingNotification;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BookingNotificationResource extends Resource
{
    use AuthorizesPlatformOperations;

    protected static ?string $model = BookingNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationLabel = 'Booking notifications';

    protected static ?string $modelLabel = 'booking notification';

    protected static ?string $pluralModelLabel = 'booking notifications';

    public static function shouldRegisterNavigation(): bool
    {
        return self::isPlatformUser();
    }

    public static function canViewAny(): bool
    {
        return self::isPlatformUser();
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
            Section::make('Details')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('id'),
                        TextEntry::make('booking_id')->label('Booking'),
                        TextEntry::make('event')->badge(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('channel'),
                        TextEntry::make('recipient_name')->label('Recipient'),
                        TextEntry::make('recipient_phone')->label('Phone'),
                        TextEntry::make('sent_at')->dateTime()->placeholder('—'),
                        TextEntry::make('failed_at')->dateTime()->placeholder('—'),
                    ]),
                ]),
            Section::make('Content')
                ->schema([
                    TextEntry::make('title')->columnSpanFull(),
                    TextEntry::make('message')->markdown()->columnSpanFull(),
                ]),
            Section::make('Failure')
                ->collapsed()
                ->schema([
                    TextEntry::make('failure_reason')->markdown()->columnSpanFull()->placeholder('—'),
                ]),
            Section::make('System')
                ->collapsed()
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                    ]),
                ]),
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

<?php

namespace App\Filament\Platform\Resources\SupportTickets;

use App\Filament\Platform\Resources\SupportTickets\Pages\CreateSupportTicket;
use App\Filament\Platform\Resources\SupportTickets\Pages\EditSupportTicket;
use App\Filament\Platform\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Platform\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Filament\Platform\Resources\SupportTickets\Schemas\SupportTicketForm;
use App\Filament\Platform\Resources\SupportTickets\Schemas\SupportTicketInfolist;
use App\Filament\Platform\Resources\SupportTickets\Tables\SupportTicketsTable;
use App\Models\SupportTicket;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Support tickets';

    protected static ?string $modelLabel = 'support ticket';

    protected static ?string $pluralModelLabel = 'support tickets';

    private static function isPlatformUser(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return (bool) $user?->hasAnyRole(['super_admin', 'operations_admin']);
    }

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
        return self::isPlatformUser();
    }

    public static function canEdit($record): bool
    {
        return self::isPlatformUser();
    }

    public static function canView($record): bool
    {
        return self::isPlatformUser();
    }

    public static function canDelete($record): bool
    {
        return self::isPlatformUser();
    }

    public static function form(Schema $schema): Schema
    {
        return SupportTicketForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupportTicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportTicketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
            'create' => CreateSupportTicket::route('/create'),
            'view' => ViewSupportTicket::route('/{record}'),
            'edit' => EditSupportTicket::route('/{record}/edit'),
        ];
    }
}

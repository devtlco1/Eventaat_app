<?php

namespace App\Filament\Platform\Resources\SupportTickets;

use App\Filament\Concerns\GrantsPlatformOperationsCrud;
use App\Filament\Platform\Resources\SupportTickets\Pages\CreateSupportTicket;
use App\Filament\Platform\Resources\SupportTickets\Pages\EditSupportTicket;
use App\Filament\Platform\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Platform\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Filament\Platform\Resources\SupportTickets\RelationManagers\SupportTicketActivitiesRelationManager;
use App\Filament\Platform\Resources\SupportTickets\Schemas\SupportTicketForm;
use App\Filament\Platform\Resources\SupportTickets\Schemas\SupportTicketInfolist;
use App\Filament\Platform\Resources\SupportTickets\Tables\SupportTicketsTable;
use App\Models\SupportTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupportTicketResource extends Resource
{
    use GrantsPlatformOperationsCrud;

    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 22;

    protected static ?string $navigationLabel = 'Support tickets';

    protected static ?string $modelLabel = 'support ticket';

    protected static ?string $pluralModelLabel = 'support tickets';

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
        return [
            SupportTicketActivitiesRelationManager::class,
        ];
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

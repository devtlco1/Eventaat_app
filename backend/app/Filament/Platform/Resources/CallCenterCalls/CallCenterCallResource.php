<?php

namespace App\Filament\Platform\Resources\CallCenterCalls;

use App\Filament\Platform\Resources\CallCenterCalls\Pages\CreateCallCenterCall;
use App\Filament\Platform\Resources\CallCenterCalls\Pages\EditCallCenterCall;
use App\Filament\Platform\Resources\CallCenterCalls\Pages\ListCallCenterCalls;
use App\Filament\Platform\Resources\CallCenterCalls\Pages\ViewCallCenterCall;
use App\Filament\Platform\Resources\CallCenterCalls\Schemas\CallCenterCallForm;
use App\Filament\Platform\Resources\CallCenterCalls\Schemas\CallCenterCallInfolist;
use App\Filament\Platform\Resources\CallCenterCalls\Tables\CallCenterCallsTable;
use App\Models\CallCenterCall;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CallCenterCallResource extends Resource
{
    protected static ?string $model = CallCenterCall::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 27;

    protected static ?string $navigationLabel = 'Call logs';

    protected static ?string $modelLabel = 'call log';

    protected static ?string $pluralModelLabel = 'call logs';

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
        return CallCenterCallForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CallCenterCallInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CallCenterCallsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCallCenterCalls::route('/'),
            'create' => CreateCallCenterCall::route('/create'),
            'view' => ViewCallCenterCall::route('/{record}'),
            'edit' => EditCallCenterCall::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Platform\Resources\CallCenterCalls;

use App\Filament\Concerns\GrantsPlatformOperationsCrud;
use App\Filament\Platform\Resources\CallCenterCalls\Pages\CreateCallCenterCall;
use App\Filament\Platform\Resources\CallCenterCalls\Pages\EditCallCenterCall;
use App\Filament\Platform\Resources\CallCenterCalls\Pages\ListCallCenterCalls;
use App\Filament\Platform\Resources\CallCenterCalls\Pages\ViewCallCenterCall;
use App\Filament\Platform\Resources\CallCenterCalls\Schemas\CallCenterCallForm;
use App\Filament\Platform\Resources\CallCenterCalls\Schemas\CallCenterCallInfolist;
use App\Filament\Platform\Resources\CallCenterCalls\Tables\CallCenterCallsTable;
use App\Models\CallCenterCall;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CallCenterCallResource extends Resource
{
    use GrantsPlatformOperationsCrud;

    protected static ?string $model = CallCenterCall::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 27;

    protected static ?string $navigationLabel = 'Call logs';

    protected static ?string $modelLabel = 'call log';

    protected static ?string $pluralModelLabel = 'call logs';

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

<?php

namespace App\Filament\Platform\Resources\Permissions;

use App\Filament\Concerns\AuthorizesSuperAdmin;
use App\Filament\Platform\Resources\Permissions\Pages\ListPermissions;
use App\Filament\Platform\Resources\Permissions\Pages\ViewPermission;
use App\Filament\Platform\Resources\Permissions\Schemas\PermissionInfolist;
use App\Filament\Platform\Resources\Permissions\Tables\PermissionsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class PermissionResource extends Resource
{
    use AuthorizesSuperAdmin;

    protected static ?string $model = Permission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVariable;

    protected static string|\UnitEnum|null $navigationGroup = 'Access Management';

    protected static ?int $navigationSort = 33;

    protected static ?string $navigationLabel = 'Permissions';

    protected static ?string $recordTitleAttribute = 'name';

    public static function infolist(Schema $schema): Schema
    {
        return PermissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermissionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
            'view' => ViewPermission::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('roles');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::authIsSuperAdmin();
    }

    public static function canViewAny(): bool
    {
        return static::authIsSuperAdmin();
    }

    public static function canView(Model $record): bool
    {
        return static::authIsSuperAdmin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}

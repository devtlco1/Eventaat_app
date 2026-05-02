<?php

namespace App\Filament\Platform\Resources\Roles;

use App\Filament\Concerns\AuthorizesSuperAdmin;
use App\Filament\Platform\Resources\Roles\Pages\CreateRole;
use App\Filament\Platform\Resources\Roles\Pages\EditRole;
use App\Filament\Platform\Resources\Roles\Pages\ListRoles;
use App\Filament\Platform\Resources\Roles\Schemas\RoleForm;
use App\Filament\Platform\Resources\Roles\Tables\RolesTable;
use App\Support\Platform\CoreRoles;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    use AuthorizesSuperAdmin;

    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Access Management';

    protected static ?int $navigationSort = 32;

    protected static ?string $navigationLabel = 'Roles';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount(['permissions', 'users']);
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
        return static::authIsSuperAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return static::authIsSuperAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        if (! static::authIsSuperAdmin()) {
            return false;
        }

        if (! $record instanceof Role) {
            return false;
        }

        return ! CoreRoles::isCore($record->name);
    }
}

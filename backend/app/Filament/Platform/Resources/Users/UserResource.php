<?php

namespace App\Filament\Platform\Resources\Users;

use App\Filament\Platform\Resources\Users\Pages\CreateUser;
use App\Filament\Platform\Resources\Users\Pages\EditUser;
use App\Filament\Platform\Resources\Users\Pages\ListUsers;
use App\Filament\Platform\Resources\Users\Pages\ViewUser;
use App\Filament\Platform\Resources\Users\RelationManagers\CustomerBookingsRelationManager;
use App\Filament\Platform\Resources\Users\RelationManagers\RestaurantReviewsRelationManager;
use App\Filament\Platform\Resources\Users\Schemas\UserForm;
use App\Filament\Platform\Resources\Users\Schemas\UserInfolist;
use App\Filament\Platform\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Access Management';

    protected static ?int $navigationSort = 31;

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    /**
     * @return array<class-string>
     */
    public static function getRelations(): array
    {
        return [
            CustomerBookingsRelationManager::class,
            RestaurantReviewsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone'];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['roles']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::platformViewer();
    }

    protected static function platformViewer(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isPlatformOperator();
    }

    protected static function superAdmin(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasRole('super_admin');
    }

    public static function canViewAny(): bool
    {
        return static::platformViewer();
    }

    public static function canView(Model $record): bool
    {
        return static::platformViewer();
    }

    public static function canCreate(): bool
    {
        return static::superAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        if (! static::platformViewer()) {
            return false;
        }

        if (! $record instanceof User) {
            return false;
        }

        if ($record->hasRole('super_admin') && ! static::superAdmin()) {
            return false;
        }

        return true;
    }

    public static function canDelete(Model $record): bool
    {
        if (! static::superAdmin()) {
            return false;
        }

        if (! $record instanceof User) {
            return false;
        }

        $editor = Filament::auth()->user();
        if ($editor instanceof User && $record->is($editor)) {
            return false;
        }

        if ($record->hasRole('super_admin')) {
            return false;
        }

        return true;
    }
}

<?php

namespace App\Filament\Restaurant\Resources\RestaurantStaffAssignments;

use App\Filament\Restaurant\Resources\RestaurantStaffAssignments\Pages\CreateRestaurantStaffAssignment;
use App\Filament\Restaurant\Resources\RestaurantStaffAssignments\Pages\EditRestaurantStaffAssignment;
use App\Filament\Restaurant\Resources\RestaurantStaffAssignments\Pages\ListRestaurantStaffAssignments;
use App\Filament\Restaurant\Resources\RestaurantStaffAssignments\Pages\ViewRestaurantStaffAssignment;
use App\Filament\Restaurant\Resources\RestaurantStaffAssignments\Schemas\RestaurantStaffAssignmentForm;
use App\Filament\Restaurant\Resources\RestaurantStaffAssignments\Schemas\RestaurantStaffAssignmentInfolist;
use App\Filament\Restaurant\Resources\RestaurantStaffAssignments\Tables\RestaurantStaffAssignmentsTable;
use App\Models\RestaurantStaffAssignment;
use App\Models\User;
use App\Support\RestaurantPanelScope;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantStaffAssignmentResource extends Resource
{
    protected static ?string $model = RestaurantStaffAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant Setup';

    protected static ?int $navigationSort = 50;

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        return RestaurantPanelScope::staffAssignments($user);
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return (bool) $user?->canManageRestaurantStructure();
    }

    public static function canEdit($record): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if (! $user || ! $user->canManageRestaurantStructure()) {
            return false;
        }

        return RestaurantPanelScope::staffAssignments($user)->whereKey($record)->exists();
    }

    public static function canView($record): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return $user
            ? RestaurantPanelScope::staffAssignments($user)->whereKey($record)->exists()
            : false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return RestaurantStaffAssignmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RestaurantStaffAssignmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantStaffAssignmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurantStaffAssignments::route('/'),
            'create' => CreateRestaurantStaffAssignment::route('/create'),
            'view' => ViewRestaurantStaffAssignment::route('/{record}'),
            'edit' => EditRestaurantStaffAssignment::route('/{record}/edit'),
        ];
    }
}

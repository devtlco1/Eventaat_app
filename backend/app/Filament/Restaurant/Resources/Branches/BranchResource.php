<?php

namespace App\Filament\Restaurant\Resources\Branches;

use App\Filament\Restaurant\Resources\Branches\Pages\CreateBranch;
use App\Filament\Restaurant\Resources\Branches\Pages\EditBranch;
use App\Filament\Restaurant\Resources\Branches\Pages\ListBranches;
use App\Filament\Restaurant\Resources\Branches\Pages\ViewBranch;
use App\Filament\Restaurant\Resources\Branches\RelationManagers\BranchAvailabilityRulesRelationManager;
use App\Filament\Restaurant\Resources\Branches\Schemas\BranchForm;
use App\Filament\Restaurant\Resources\Branches\Schemas\BranchInfolist;
use App\Filament\Restaurant\Resources\Branches\Tables\BranchesTable;
use App\Models\Branch;
use App\Models\User;
use App\Support\RestaurantPanelScope;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant Setup';

    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        return RestaurantPanelScope::branches($user);
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return (bool) $user?->isRestaurantOwner();
    }

    public static function canEdit($record): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if (! $user || ! $user->canManageRestaurantStructure()) {
            return false;
        }

        return RestaurantPanelScope::branches($user)->whereKey($record)->exists();
    }

    public static function canView($record): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return $user
            ? RestaurantPanelScope::branches($user)->whereKey($record)->exists()
            : false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return BranchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BranchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BranchAvailabilityRulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBranches::route('/'),
            'create' => CreateBranch::route('/create'),
            'view' => ViewBranch::route('/{record}'),
            'edit' => EditBranch::route('/{record}/edit'),
        ];
    }
}

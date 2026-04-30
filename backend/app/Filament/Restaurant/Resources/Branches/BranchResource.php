<?php

namespace App\Filament\Restaurant\Resources\Branches;

use App\Filament\Restaurant\Resources\Branches\Pages\CreateBranch;
use App\Filament\Restaurant\Resources\Branches\Pages\EditBranch;
use App\Filament\Restaurant\Resources\Branches\Pages\ListBranches;
use App\Filament\Restaurant\Resources\Branches\Pages\ViewBranch;
use App\Filament\Restaurant\Resources\Branches\Schemas\BranchForm;
use App\Filament\Restaurant\Resources\Branches\Schemas\BranchInfolist;
use App\Filament\Restaurant\Resources\Branches\Tables\BranchesTable;
use App\Models\Branch;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant Setup';

    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        return RestaurantPanelScope::branches($user);
    }

    public static function canCreate(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        return (bool) $user?->hasRole('restaurant_owner');
    }

    public static function canEdit($record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        if (! $user || ! $user->hasAnyRole(['restaurant_owner', 'branch_manager'])) {
            return false;
        }

        return RestaurantPanelScope::branches($user)->whereKey($record)->exists();
    }

    public static function canView($record): bool
    {
        /** @var \App\Models\User|null $user */
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
            //
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

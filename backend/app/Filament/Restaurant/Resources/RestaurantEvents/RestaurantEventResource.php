<?php

namespace App\Filament\Restaurant\Resources\RestaurantEvents;

use App\Filament\Restaurant\Resources\RestaurantEvents\Pages\CreateRestaurantEvent;
use App\Filament\Restaurant\Resources\RestaurantEvents\Pages\EditRestaurantEvent;
use App\Filament\Restaurant\Resources\RestaurantEvents\Pages\ListRestaurantEvents;
use App\Filament\Restaurant\Resources\RestaurantEvents\Pages\ViewRestaurantEvent;
use App\Filament\Restaurant\Resources\RestaurantEvents\Schemas\RestaurantEventForm;
use App\Filament\Restaurant\Resources\RestaurantEvents\Schemas\RestaurantEventInfolist;
use App\Filament\Restaurant\Resources\RestaurantEvents\Tables\RestaurantEventsTable;
use App\Models\RestaurantEvent;
use App\Models\User;
use App\Support\RestaurantPanelScope;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantEventResource extends Resource
{
    protected static ?string $model = RestaurantEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Event nights';

    protected static ?string $modelLabel = 'event night';

    protected static ?string $pluralModelLabel = 'event nights';

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        $restaurantIds = $user->scopedRestaurantIds();
        $branchIds = $user->scopedBranchIds();

        $query = RestaurantEvent::query()->whereIn('restaurant_id', $restaurantIds);

        // Branch-scoped staff can only manage branch-scoped events (branch_id required).
        if (count($branchIds)) {
            $query->whereIn('branch_id', $branchIds);
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return (bool) $user?->hasAnyRole(['restaurant_owner', 'branch_manager', 'restaurant_host']);
    }

    public static function canEdit($record): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if (! $user || ! $user->hasAnyRole(['restaurant_owner', 'branch_manager', 'restaurant_host'])) {
            return false;
        }

        return self::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function canView($record): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return $user ? self::getEloquentQuery()->whereKey($record)->exists() : false;
    }

    public static function canDelete($record): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return (bool) $user?->hasRole('restaurant_owner') && self::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function form(Schema $schema): Schema
    {
        return RestaurantEventForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RestaurantEventInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantEventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurantEvents::route('/'),
            'create' => CreateRestaurantEvent::route('/create'),
            'view' => ViewRestaurantEvent::route('/{record}'),
            'edit' => EditRestaurantEvent::route('/{record}/edit'),
        ];
    }
}


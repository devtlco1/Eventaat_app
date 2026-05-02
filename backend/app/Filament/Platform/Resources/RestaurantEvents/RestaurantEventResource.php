<?php

namespace App\Filament\Platform\Resources\RestaurantEvents;

use App\Filament\Platform\Resources\RestaurantEvents\Pages\CreateRestaurantEvent;
use App\Filament\Platform\Resources\RestaurantEvents\Pages\EditRestaurantEvent;
use App\Filament\Platform\Resources\RestaurantEvents\Pages\ListRestaurantEvents;
use App\Filament\Platform\Resources\RestaurantEvents\Pages\ViewRestaurantEvent;
use App\Filament\Platform\Resources\RestaurantEvents\RelationManagers\BookingsRelationManager;
use App\Filament\Platform\Resources\RestaurantEvents\Schemas\RestaurantEventForm;
use App\Filament\Platform\Resources\RestaurantEvents\Schemas\RestaurantEventInfolist;
use App\Filament\Platform\Resources\RestaurantEvents\Tables\RestaurantEventsTable;
use App\Models\RestaurantEvent;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RestaurantEventResource extends Resource
{
    protected static ?string $model = RestaurantEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 17;

    protected static ?string $navigationLabel = 'Event nights';

    protected static ?string $modelLabel = 'event night';

    protected static ?string $pluralModelLabel = 'event nights';

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
        return [
            BookingsRelationManager::class,
        ];
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

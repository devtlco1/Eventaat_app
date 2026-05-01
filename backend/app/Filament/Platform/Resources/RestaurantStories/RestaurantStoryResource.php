<?php

namespace App\Filament\Platform\Resources\RestaurantStories;

use App\Filament\Platform\Resources\RestaurantStories\Pages\CreateRestaurantStory;
use App\Filament\Platform\Resources\RestaurantStories\Pages\EditRestaurantStory;
use App\Filament\Platform\Resources\RestaurantStories\Pages\ListRestaurantStories;
use App\Filament\Platform\Resources\RestaurantStories\Pages\ViewRestaurantStory;
use App\Filament\Platform\Resources\RestaurantStories\Schemas\RestaurantStoryForm;
use App\Filament\Platform\Resources\RestaurantStories\Schemas\RestaurantStoryInfolist;
use App\Filament\Platform\Resources\RestaurantStories\Tables\RestaurantStoriesTable;
use App\Models\RestaurantStory;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RestaurantStoryResource extends Resource
{
    protected static ?string $model = RestaurantStory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 19;

    protected static ?string $navigationLabel = 'Stories';

    protected static ?string $modelLabel = 'story';

    protected static ?string $pluralModelLabel = 'stories';

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
        return RestaurantStoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RestaurantStoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantStoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurantStories::route('/'),
            'create' => CreateRestaurantStory::route('/create'),
            'view' => ViewRestaurantStory::route('/{record}'),
            'edit' => EditRestaurantStory::route('/{record}/edit'),
        ];
    }
}


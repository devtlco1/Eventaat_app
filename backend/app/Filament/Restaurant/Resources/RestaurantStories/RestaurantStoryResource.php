<?php

namespace App\Filament\Restaurant\Resources\RestaurantStories;

use App\Filament\Restaurant\Resources\RestaurantStories\Pages\CreateRestaurantStory;
use App\Filament\Restaurant\Resources\RestaurantStories\Pages\EditRestaurantStory;
use App\Filament\Restaurant\Resources\RestaurantStories\Pages\ListRestaurantStories;
use App\Filament\Restaurant\Resources\RestaurantStories\Pages\ViewRestaurantStory;
use App\Filament\Restaurant\Resources\RestaurantStories\RelationManagers\StoryItemsRelationManager;
use App\Filament\Restaurant\Resources\RestaurantStories\Schemas\RestaurantStoryForm;
use App\Filament\Restaurant\Resources\RestaurantStories\Schemas\RestaurantStoryInfolist;
use App\Filament\Restaurant\Resources\RestaurantStories\Tables\RestaurantStoriesTable;
use App\Models\RestaurantStory;
use App\Models\User;
use App\Support\RestaurantPanelScope;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantStoryResource extends Resource
{
    protected static ?string $model = RestaurantStory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 32;

    protected static ?string $navigationLabel = 'Stories';

    protected static ?string $modelLabel = 'story';

    protected static ?string $pluralModelLabel = 'stories';

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        return RestaurantPanelScope::stories($user);
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

        if (! $user || ! $user->hasRole('restaurant_owner')) {
            return false;
        }

        return self::getEloquentQuery()->whereKey($record)->exists();
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
        return [
            StoryItemsRelationManager::class,
        ];
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

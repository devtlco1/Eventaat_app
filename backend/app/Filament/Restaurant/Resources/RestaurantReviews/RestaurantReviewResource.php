<?php

namespace App\Filament\Restaurant\Resources\RestaurantReviews;

use App\Filament\Restaurant\Resources\RestaurantReviews\Pages\ListRestaurantReviews;
use App\Filament\Restaurant\Resources\RestaurantReviews\Pages\ViewRestaurantReview;
use App\Filament\Restaurant\Resources\RestaurantReviews\Schemas\RestaurantReviewInfolist;
use App\Filament\Restaurant\Resources\RestaurantReviews\Tables\RestaurantReviewsTable;
use App\Models\RestaurantReview;
use App\Models\User;
use App\Support\RestaurantPanelScope;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantReviewResource extends Resource
{
    protected static ?string $model = RestaurantReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 32;

    protected static ?string $navigationLabel = 'Reviews';

    protected static ?string $modelLabel = 'review';

    protected static ?string $pluralModelLabel = 'reviews';

    public static function getEloquentQuery(): Builder
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        return RestaurantPanelScope::reviews($user);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canView($record): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return $user ? self::getEloquentQuery()->whereKey($record)->exists() : false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return RestaurantReviewInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantReviewsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurantReviews::route('/'),
            'view' => ViewRestaurantReview::route('/{record}'),
        ];
    }
}

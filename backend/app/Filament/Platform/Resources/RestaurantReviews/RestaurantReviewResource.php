<?php

namespace App\Filament\Platform\Resources\RestaurantReviews;

use App\Filament\Concerns\GrantsPlatformOperationsCrud;
use App\Filament\Platform\Resources\RestaurantReviews\Pages\CreateRestaurantReview;
use App\Filament\Platform\Resources\RestaurantReviews\Pages\EditRestaurantReview;
use App\Filament\Platform\Resources\RestaurantReviews\Pages\ListRestaurantReviews;
use App\Filament\Platform\Resources\RestaurantReviews\Pages\ViewRestaurantReview;
use App\Filament\Platform\Resources\RestaurantReviews\Schemas\RestaurantReviewForm;
use App\Filament\Platform\Resources\RestaurantReviews\Schemas\RestaurantReviewInfolist;
use App\Filament\Platform\Resources\RestaurantReviews\Tables\RestaurantReviewsTable;
use App\Models\RestaurantReview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RestaurantReviewResource extends Resource
{
    use GrantsPlatformOperationsCrud;

    protected static ?string $model = RestaurantReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 21;

    protected static ?string $navigationLabel = 'Reviews';

    protected static ?string $modelLabel = 'review';

    protected static ?string $pluralModelLabel = 'reviews';

    public static function form(Schema $schema): Schema
    {
        return RestaurantReviewForm::configure($schema);
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
            'create' => CreateRestaurantReview::route('/create'),
            'view' => ViewRestaurantReview::route('/{record}'),
            'edit' => EditRestaurantReview::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Platform\Resources\Users\RelationManagers;

use App\Filament\Platform\Resources\RestaurantReviews\RestaurantReviewResource;
use App\Filament\Support\FilamentSchemaLayout;
use App\Models\RestaurantReview;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RestaurantReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'restaurantReviews';

    protected static ?string $title = 'Reviews';

    public function form(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('restaurant.name')->label('Restaurant')->placeholder('—'),
                TextColumn::make('rating')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('source')->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('comment')->limit(40)->toggleable(isToggledHiddenByDefault: true)->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([10, 25])
            ->recordActions([])
            ->headerActions([])
            ->bulkActions([])
            ->recordUrl(fn (RestaurantReview $record): string => RestaurantReviewResource::getUrl('edit', ['record' => $record]));
    }
}

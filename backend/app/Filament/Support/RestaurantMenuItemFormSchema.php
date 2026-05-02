<?php

namespace App\Filament\Support;

use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuItem;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

final class RestaurantMenuItemFormSchema
{
    /**
     * Full modal form schema for creating/editing menu items within a category.
     *
     * @return array<int, Section>
     */
    public static function sections(int $categoryId): array
    {
        return [
            Section::make()
                ->compact()
                ->schema([
                    FileUpload::make('image_path')
                        ->label('Image')
                        ->image()
                        ->imagePreviewHeight('10rem')
                        ->disk('public')
                        ->directory('menus/items')
                        ->visibility('public')
                        ->maxFiles(1)
                        ->fetchFileInformation(false)
                        ->nullable()
                        ->downloadable(false)
                        ->openable()
                        ->columnSpanFull(),
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('Item name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('currency')
                            ->label('Currency')
                            ->default('IQD')
                            ->maxLength(8)
                            ->required(),
                        TextInput::make('display_order')
                            ->label('Display order')
                            ->numeric()
                            ->default(fn (): int => (int) ((RestaurantMenuItem::query()
                                ->where('restaurant_menu_category_id', $categoryId)
                                ->max('display_order')) ?? -1) + 1)
                            ->minValue(0),
                    ]),
                    Grid::make(2)->schema([
                        Toggle::make('is_available')
                            ->label('Available')
                            ->default(true),
                        Toggle::make('is_featured')
                            ->label('Featured')
                            ->default(false),
                    ]),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->label('Internal notes')
                        ->rows(2)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * Item form with category picker scoped to a menu (structured edit UI).
     *
     * @return array<int, Section>
     */
    public static function sectionsForMenu(int $menuId): array
    {
        return [
            Section::make()
                ->compact()
                ->schema([
                    Select::make('restaurant_menu_category_id')
                        ->label('Category')
                        ->required()
                        ->searchable()
                        ->options(fn (): array => RestaurantMenuCategory::query()
                            ->where('restaurant_menu_id', $menuId)
                            ->orderBy('display_order')
                            ->pluck('name', 'id')
                            ->all()),
                    FileUpload::make('image_path')
                        ->label('Image')
                        ->image()
                        ->imagePreviewHeight('10rem')
                        ->disk('public')
                        ->directory('menus/items')
                        ->visibility('public')
                        ->maxFiles(1)
                        ->fetchFileInformation(false)
                        ->nullable()
                        ->downloadable(false)
                        ->openable()
                        ->columnSpanFull(),
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('Item name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('currency')
                            ->label('Currency')
                            ->default('IQD')
                            ->maxLength(8)
                            ->required(),
                        TextInput::make('display_order')
                            ->label('Display order')
                            ->numeric()
                            ->default(function (Get $get): int {
                                $cid = $get('restaurant_menu_category_id');
                                if (blank($cid)) {
                                    return 0;
                                }

                                return (int) ((RestaurantMenuItem::query()
                                    ->where('restaurant_menu_category_id', $cid)
                                    ->max('display_order')) ?? -1) + 1;
                            })
                            ->minValue(0),
                    ]),
                    Grid::make(2)->schema([
                        Toggle::make('is_available')
                            ->label('Available')
                            ->default(true),
                        Toggle::make('is_featured')
                            ->label('Featured')
                            ->default(false),
                    ]),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->label('Internal notes')
                        ->rows(2)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ];
    }
}

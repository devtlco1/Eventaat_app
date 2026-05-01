<?php

namespace App\Filament\Support;

use App\Models\RestaurantMenu;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

final class RestaurantMenuContentRepeater
{
    public static function menuContentSection(): Section
    {
        return Section::make('Menu content')
            ->description('Add categories, then dishes under each category. Order is applied top to bottom.')
            ->visible(fn (Get $get): bool => $get('menu_mode') === RestaurantMenu::MODE_STRUCTURED)
            ->schema([
                Repeater::make('categories')
                    ->relationship()
                    ->orderColumn('display_order')
                    ->defaultItems(0)
                    ->addActionLabel('Add category')
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                    ->schema([
                        TextInput::make('name')
                            ->label('Category name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Category description')
                            ->rows(2)
                            ->nullable(),
                        Grid::make(2)->schema([
                            TextInput::make('display_order')
                                ->label('Display order')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ]),
                        Repeater::make('items')
                            ->relationship()
                            ->orderColumn('display_order')
                            ->defaultItems(0)
                            ->addActionLabel('Add item')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->schema([
                                FileUpload::make('image_path')
                                    ->label('Image')
                                    ->disk('public')
                                    ->directory('menus/items')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['image/*'])
                                    ->maxFiles(1)
                                    ->nullable()
                                    ->downloadable(false),
                                TextInput::make('name')
                                    ->label('Item name')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->label('Description')
                                    ->rows(2)
                                    ->nullable(),
                                Grid::make(2)->schema([
                                    TextInput::make('price')
                                        ->numeric()
                                        ->minValue(0)
                                        ->nullable(),
                                    TextInput::make('currency')
                                        ->default('IQD')
                                        ->maxLength(8)
                                        ->required(),
                                ]),
                                Grid::make(2)->schema([
                                    Toggle::make('is_available')
                                        ->label('Available')
                                        ->default(true),
                                    Toggle::make('is_featured')
                                        ->label('Featured')
                                        ->default(false),
                                ]),
                                TextInput::make('display_order')
                                    ->label('Display order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                Textarea::make('notes')
                                    ->label('Internal notes')
                                    ->rows(2)
                                    ->nullable(),
                            ]),
                    ]),
            ]);
    }
}

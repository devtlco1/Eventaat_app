<?php

namespace App\Filament\Restaurant\Resources\RestaurantMenus\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantMenuCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Category')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('display_order')
                            ->label('Display order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ]),
                    Textarea::make('description')->rows(3)->nullable()->columnSpanFull(),
                    Toggle::make('is_active')->label('Active')->default(true),
                ]),
        ]);
    }
}

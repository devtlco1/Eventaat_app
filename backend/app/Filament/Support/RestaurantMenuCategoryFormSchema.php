<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

final class RestaurantMenuCategoryFormSchema
{
    /**
     * @return array<int, Section>
     */
    public static function sections(): array
    {
        return [
            Section::make()
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('display_order')
                            ->label('Display order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),
                ]),
        ];
    }
}

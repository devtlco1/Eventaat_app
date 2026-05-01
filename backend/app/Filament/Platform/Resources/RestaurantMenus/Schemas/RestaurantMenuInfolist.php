<?php

namespace App\Filament\Platform\Resources\RestaurantMenus\Schemas;

use App\Models\RestaurantMenu;
use App\Models\RestaurantMenuItem;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;

class RestaurantMenuInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('title'),
                            TextEntry::make('slug'),
                            TextEntry::make('restaurant.name')->label('Restaurant'),
                            TextEntry::make('branch.name')->label('Branch')->placeholder('—'),
                            TextEntry::make('menu_mode')->label('Menu mode')->badge(),
                            TextEntry::make('status')->badge(),
                            TextEntry::make('display_order')->label('Display order'),
                        ]),
                    ]),

                Section::make('Menu preview')
                    ->visible(fn (RestaurantMenu $record): bool => $record->isStructured())
                    ->schema([
                        RepeatableEntry::make('categories')
                            ->hiddenLabel()
                            ->placeholder('No categories yet.')
                            ->columnSpanFull()
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Category')
                                    ->weight(FontWeight::SemiBold)
                                    ->columnSpanFull(),
                                RepeatableEntry::make('items')
                                    ->hiddenLabel()
                                    ->placeholder('No items in this category.')
                                    ->schema([
                                        Grid::make(12)->schema([
                                            ImageEntry::make('image_path')
                                                ->hiddenLabel()
                                                ->disk('public')
                                                ->imageHeight(48)
                                                ->columnSpan(2)
                                                ->visible(fn (ImageEntry $component): bool => filled($component->getRecord()?->image_path)),
                                            TextEntry::make('name')
                                                ->label('Item')
                                                ->columnSpan(3),
                                            TextEntry::make('description')
                                                ->label('Description')
                                                ->placeholder('—')
                                                ->columnSpan(3),
                                            TextEntry::make('price')
                                                ->label('Price')
                                                ->columnSpan(2)
                                                ->formatStateUsing(function ($state, TextEntry $component): string {
                                                    $record = $component->getRecord();
                                                    if (! $record instanceof RestaurantMenuItem) {
                                                        return '—';
                                                    }

                                                    return $state === null || $state === ''
                                                        ? '—'
                                                        : number_format((float) $state, 2).' '.$record->currency;
                                                }),
                                            TextEntry::make('is_available')
                                                ->label('Avail.')
                                                ->badge()
                                                ->columnSpan(1)
                                                ->formatStateUsing(fn (?bool $state): string => $state ? 'Yes' : 'No')
                                                ->color(fn (?bool $state): string => $state ? 'success' : 'gray'),
                                            TextEntry::make('is_featured')
                                                ->label('Feat.')
                                                ->badge()
                                                ->columnSpan(1)
                                                ->formatStateUsing(fn (?bool $state): string => $state ? 'Yes' : '—')
                                                ->color(fn (?bool $state): string => $state ? 'warning' : 'gray'),
                                        ]),
                                    ]),
                            ]),
                    ]),

                Section::make('PDF menu')
                    ->visible(fn (RestaurantMenu $record): bool => $record->menu_mode === RestaurantMenu::MODE_PDF_UPLOAD)
                    ->schema([
                        TextEntry::make('menu_file_path')
                            ->label('PDF')
                            ->placeholder('No PDF uploaded.')
                            ->formatStateUsing(fn (?string $state): ?string => filled($state) ? 'Open PDF menu' : null)
                            ->url(fn (RestaurantMenu $record): ?string => filled($record->menu_file_path)
                                ? Storage::disk('public')->url($record->menu_file_path)
                                : null)
                            ->openUrlInNewTab(),
                    ]),

                Section::make('External menu')
                    ->visible(fn (RestaurantMenu $record): bool => $record->menu_mode === RestaurantMenu::MODE_EXTERNAL_LINK)
                    ->schema([
                        TextEntry::make('menu_url')
                            ->label('Link')
                            ->placeholder('—')
                            ->formatStateUsing(fn (?string $state): ?string => filled($state) ? 'Open external menu' : null)
                            ->url(fn (RestaurantMenu $record): ?string => filled($record->menu_url) ? $record->menu_url : null)
                            ->openUrlInNewTab(),
                    ]),

                Section::make('Content')
                    ->collapsed()
                    ->schema([
                        TextEntry::make('description')->markdown()->columnSpanFull()->placeholder('—'),
                        TextEntry::make('notes')->markdown()->columnSpanFull()->placeholder('—'),
                    ]),

                Section::make('System')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('created_at')->dateTime(),
                            TextEntry::make('updated_at')->dateTime(),
                        ]),
                    ]),
            ]);
    }
}

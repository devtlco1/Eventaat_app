<?php

namespace App\Filament\Restaurant\Resources\RestaurantMenus\Schemas;

use App\Models\RestaurantMenu;
use App\Models\RestaurantMenuItem;
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
                                        Grid::make(1)->schema([
                                            TextEntry::make('name')->label('Item'),
                                            TextEntry::make('price')
                                                ->label('Price')
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
                                                ->label('Availability')
                                                ->badge()
                                                ->formatStateUsing(fn (?bool $state): string => $state ? 'Available' : 'Unavailable')
                                                ->color(fn (?bool $state): string => $state ? 'success' : 'gray'),
                                            TextEntry::make('description')
                                                ->label('Description')
                                                ->placeholder('—')
                                                ->columnSpanFull()
                                                ->visible(fn (TextEntry $component): bool => filled($component->getRecord()?->description)),
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

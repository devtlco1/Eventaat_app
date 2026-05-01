<?php

namespace App\Filament\Platform\Resources\RestaurantMenus\Schemas;

use App\Models\RestaurantMenu;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
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

                Section::make('PDF or external menu')
                    ->schema([
                        TextEntry::make('menu_file_path')
                            ->label('Menu PDF')
                            ->placeholder('—')
                            ->visible(fn (RestaurantMenu $record): bool => $record->menu_mode === RestaurantMenu::MODE_PDF_UPLOAD)
                            ->formatStateUsing(fn (?string $state): ?string => filled($state) ? __('Open / download PDF') : null)
                            ->url(fn (RestaurantMenu $record): ?string => filled($record->menu_file_path)
                                ? Storage::disk('public')->url($record->menu_file_path)
                                : null)
                            ->openUrlInNewTab(),
                        TextEntry::make('menu_url')
                            ->label('Menu URL')
                            ->placeholder('—')
                            ->visible(fn (RestaurantMenu $record): bool => $record->menu_mode === RestaurantMenu::MODE_EXTERNAL_LINK)
                            ->url(fn (RestaurantMenu $record): ?string => filled($record->menu_url) ? $record->menu_url : null)
                            ->openUrlInNewTab(),
                    ])
                    ->collapsed(),

                Section::make('Content')
                    ->schema([
                        TextEntry::make('description')->markdown()->columnSpanFull()->placeholder('—'),
                        TextEntry::make('notes')->markdown()->columnSpanFull()->placeholder('—'),
                    ])
                    ->collapsed(),

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

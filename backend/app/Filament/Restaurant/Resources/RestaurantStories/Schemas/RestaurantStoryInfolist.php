<?php

namespace App\Filament\Restaurant\Resources\RestaurantStories\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantStoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Details')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('title'),
                        TextEntry::make('slug'),
                        TextEntry::make('restaurant.name')->label('Restaurant'),
                        TextEntry::make('branch.name')->label('Branch')->placeholder('—'),
                    ]),
                ]),

            Section::make('Status')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('story_type')->label('Story type')->badge(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('display_order')->label('Display order'),
                    ]),
                ]),

            Section::make('Timing')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('starts_at')->label('Starts at')->dateTime()->placeholder('—'),
                        TextEntry::make('ends_at')->label('Ends at')->dateTime()->placeholder('—'),
                    ]),
                ]),

            Section::make('Content')
                ->schema([
                    TextEntry::make('media_url')->label('Media URL')->columnSpanFull()->placeholder('—'),
                    TextEntry::make('body')->markdown()->columnSpanFull()->placeholder('—'),
                    TextEntry::make('cta_label')->label('CTA label')->placeholder('—'),
                    TextEntry::make('cta_url')->label('CTA URL')->columnSpanFull()->placeholder('—'),
                    TextEntry::make('notes')->markdown()->columnSpanFull()->placeholder('—'),
                ]),
        ]);
    }
}


<?php

namespace App\Filament\Platform\Resources\RestaurantStories\Schemas;

use App\Models\RestaurantStory;
use App\Models\RestaurantStoryItem;
use Carbon\CarbonInterface;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
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
                        TextEntry::make('restaurant.name')->label('Restaurant'),
                        TextEntry::make('branch.name')->label('Branch')->placeholder('—'),
                    ]),
                ]),

            Section::make('Schedule & status')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('story_type')
                            ->label('Legacy story type')
                            ->badge()
                            ->placeholder('—')
                            ->visible(fn (RestaurantStory $record): bool => ! $record->items()->exists()),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('lifetime_mode')->label('Lifetime mode')->badge(),
                        TextEntry::make('lifetime_hours')->label('Manual lifetime hours')->placeholder('—'),
                        TextEntry::make('derived_duration')
                            ->label('Derived window')
                            ->state(function (RestaurantStory $record): ?string {
                                if (! $record->starts_at || ! $record->ends_at) {
                                    return null;
                                }

                                return $record->starts_at->diffForHumans($record->ends_at, [
                                    'parts' => 3,
                                    'syntax' => CarbonInterface::DIFF_ABSOLUTE,
                                ]);
                            }),
                    ]),
                    Grid::make(2)->schema([
                        TextEntry::make('starts_at')->label('Starts at')->dateTime()->placeholder('—'),
                        TextEntry::make('ends_at')->label('Ends at')->dateTime()->placeholder('—'),
                    ]),
                ]),

            Section::make('Story slides')
                ->schema([
                    RepeatableEntry::make('items')
                        ->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('sort_order')->label('#'),
                                TextEntry::make('item_type')->label('Slide type')->badge(),
                                TextEntry::make('item_duration_seconds')->label('Display seconds')->placeholder('—'),
                                TextEntry::make('created_at')->dateTime()->sinceTooltip(),
                            ]),
                            ViewEntry::make('preview')
                                ->view('filament.story-slide-preview')
                                ->columnSpanFull(),
                            Grid::make(2)
                                ->schema([
                                    TextEntry::make('cta_label')->label('Slide button label')->placeholder('—'),
                                    TextEntry::make('cta_url')->label('Slide link URL')->placeholder('—')->columnSpanFull(),
                                ])
                                ->visible(fn (?RestaurantStoryItem $record): bool => $record !== null
                                    && (filled($record->cta_label) || filled($record->cta_url))),
                        ])
                        ->placeholder('No slides yet'),
                ]),

            Section::make('Story CTA')
                ->collapsed()
                ->schema([
                    TextEntry::make('cta_label')->label('CTA label')->placeholder('—'),
                    TextEntry::make('cta_url')->label('CTA URL')->columnSpanFull()->placeholder('—'),
                ]),

            Section::make('Publishing')
                ->collapsed()
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('slug'),
                        TextEntry::make('display_order')->label('Display order'),
                    ]),
                ]),

            Section::make('Legacy container content')
                ->collapsed()
                ->visible(fn (RestaurantStory $record): bool => ! $record->items()->exists()
                    && $record->hasRenderableLegacyContent())
                ->schema([
                    TextEntry::make('media_url')->label('Legacy media URL')->columnSpanFull()->placeholder('—'),
                    TextEntry::make('body')->markdown()->columnSpanFull()->placeholder('—'),
                ]),

            Section::make('Notes')
                ->collapsed()
                ->schema([
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

<?php

namespace App\Filament\Restaurant\Resources\RestaurantStories\Schemas;

use App\Models\RestaurantStory;
use App\Models\RestaurantStoryItem;
use Carbon\CarbonInterface;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

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
                        TextEntry::make('story_type')->label('Legacy story type')->badge()->placeholder('—'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('display_order')->label('Display order'),
                    ]),
                ]),

            Section::make('Lifetime')
                ->schema([
                    Grid::make(3)->schema([
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

            Section::make('Story items')
                ->schema([
                    RepeatableEntry::make('items')
                        ->schema([
                            Grid::make(6)->schema([
                                TextEntry::make('sort_order')->label('#'),
                                TextEntry::make('item_type')->badge(),
                                TextEntry::make('item_duration_seconds')->label('Seconds')->placeholder('—'),
                                TextEntry::make('created_at')->dateTime()->sinceTooltip(),
                            ]),
                            ImageEntry::make('media_path')
                                ->label('Image')
                                ->disk('public')
                                ->visible(fn (?RestaurantStoryItem $record): bool => $record !== null
                                    && $record->item_type === RestaurantStoryItem::TYPE_IMAGE
                                    && filled($record->media_path))
                                ->columnSpanFull(),
                            TextEntry::make('media_path')
                                ->label('Video')
                                ->html()
                                ->columnSpanFull()
                                ->visible(fn (?RestaurantStoryItem $record): bool => $record !== null
                                    && $record->item_type === RestaurantStoryItem::TYPE_VIDEO
                                    && filled($record->media_path))
                                ->formatStateUsing(function (?string $state): string {
                                    if (! filled($state)) {
                                        return '';
                                    }

                                    $url = e(Storage::disk('public')->url((string) $state));

                                    return '<video controls style="max-width:100%;max-height:420px" src="'.$url.'"></video>';
                                }),
                            TextEntry::make('body')
                                ->label('Text')
                                ->markdown()
                                ->columnSpanFull()
                                ->visible(fn (?RestaurantStoryItem $record): bool => $record !== null
                                    && $record->item_type === RestaurantStoryItem::TYPE_TEXT),
                            Grid::make(2)->schema([
                                TextEntry::make('cta_label')->label('Item CTA label')->placeholder('—'),
                                TextEntry::make('cta_url')->label('Item CTA URL')->placeholder('—')->columnSpanFull(),
                            ]),
                        ])
                        ->placeholder('No items yet'),
                ]),

            Section::make('Legacy container content')
                ->collapsed()
                ->schema([
                    TextEntry::make('media_url')->label('Legacy media URL')->columnSpanFull()->placeholder('—'),
                    TextEntry::make('body')->markdown()->columnSpanFull()->placeholder('—'),
                    TextEntry::make('cta_label')->label('Story CTA label')->placeholder('—'),
                    TextEntry::make('cta_url')->label('Story CTA URL')->columnSpanFull()->placeholder('—'),
                    TextEntry::make('notes')->markdown()->columnSpanFull()->placeholder('—'),
                ]),
        ]);
    }
}

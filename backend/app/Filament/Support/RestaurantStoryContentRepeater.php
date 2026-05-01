<?php

namespace App\Filament\Support;

use App\Models\RestaurantStoryItem;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

final class RestaurantStoryContentRepeater
{
    public static function section(): Section
    {
        return Section::make('Story content')
            ->schema([
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Select::make('item_type')
                            ->label('Type')
                            ->required()
                            ->live()
                            ->options([
                                RestaurantStoryItem::TYPE_IMAGE => 'Image',
                                RestaurantStoryItem::TYPE_VIDEO => 'Video',
                                RestaurantStoryItem::TYPE_TEXT => 'Text',
                            ])
                            ->default(RestaurantStoryItem::TYPE_IMAGE),

                        FileUpload::make('media_path')
                            ->label('Media')
                            ->disk('public')
                            ->directory('stories/items')
                            ->visibility('public')
                            ->maxFiles(1)
                            ->nullable()
                            ->downloadable(false)
                            ->openable(false)
                            ->visible(fn (Get $get): bool => in_array($get('item_type'), [
                                RestaurantStoryItem::TYPE_IMAGE,
                                RestaurantStoryItem::TYPE_VIDEO,
                            ], true))
                            ->acceptedFileTypes(fn (Get $get): array => match ($get('item_type')) {
                                RestaurantStoryItem::TYPE_VIDEO => ['video/*'],
                                default => ['image/*'],
                            })
                            ->required(fn (Get $get): bool => in_array($get('item_type'), [
                                RestaurantStoryItem::TYPE_IMAGE,
                                RestaurantStoryItem::TYPE_VIDEO,
                            ], true)),

                        Textarea::make('body')
                            ->label('Text')
                            ->rows(5)
                            ->visible(fn (Get $get): bool => $get('item_type') === RestaurantStoryItem::TYPE_TEXT)
                            ->required(fn (Get $get): bool => $get('item_type') === RestaurantStoryItem::TYPE_TEXT),

                        TextInput::make('sort_order')
                            ->label('Sort order')
                            ->numeric()
                            ->required()
                            ->default(0),

                        TextInput::make('item_duration_seconds')
                            ->label('Duration (seconds)')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),

                        Section::make('Slide CTA (optional)')
                            ->collapsed()
                            ->schema([
                                TextInput::make('cta_label')->label('CTA label')->maxLength(255)->nullable(),
                                TextInput::make('cta_url')->label('CTA URL')->maxLength(2048)->nullable(),
                            ]),
                    ])
                    ->orderColumn('sort_order')
                    ->defaultItems(0)
                    ->addActionLabel('Add slide')
                    ->collapsible()
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): ?array {
                        return RestaurantStoryRepeaterPayload::normalizeRowForPersistence($data);
                    }),
            ]);
    }
}

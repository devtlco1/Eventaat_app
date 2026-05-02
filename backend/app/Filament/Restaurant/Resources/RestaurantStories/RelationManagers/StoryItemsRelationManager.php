<?php

namespace App\Filament\Restaurant\Resources\RestaurantStories\RelationManagers;

use App\Filament\Restaurant\Resources\RestaurantStories\RestaurantStoryResource;
use App\Filament\Support\FilamentSchemaLayout;
use App\Models\RestaurantStoryItem;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;

class StoryItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Story slides';

    protected static bool $shouldSkipAuthorization = true;

    public function form(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)
            ->components([
                Section::make()
                    ->compact()
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('item_type')
                                ->label('Slide type')
                                ->required()
                                ->options([
                                    RestaurantStoryItem::TYPE_IMAGE => 'Image',
                                    RestaurantStoryItem::TYPE_VIDEO => 'Video',
                                    RestaurantStoryItem::TYPE_TEXT => 'Text',
                                ])
                                ->default(RestaurantStoryItem::TYPE_IMAGE)
                                ->reactive(),
                            TextInput::make('sort_order')
                                ->label('Sort order')
                                ->numeric()
                                ->required()
                                ->default(0),
                            TextInput::make('item_duration_seconds')
                                ->label('Display seconds')
                                ->numeric()
                                ->minValue(1)
                                ->nullable(),
                        ]),
                        FileUpload::make('media_path')
                            ->label('Upload image/video')
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
                            ], true))
                            ->columnSpanFull(),
                        Textarea::make('body')
                            ->label('Slide text')
                            ->rows(5)
                            ->nullable()
                            ->visible(fn (Get $get): bool => $get('item_type') === RestaurantStoryItem::TYPE_TEXT)
                            ->required(fn (Get $get): bool => $get('item_type') === RestaurantStoryItem::TYPE_TEXT)
                            ->columnSpanFull(),
                        Section::make('Link button (optional)')
                            ->compact()
                            ->collapsed()
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('cta_label')->label('Button label')->maxLength(255)->nullable(),
                                    TextInput::make('cta_url')->label('Link URL')->maxLength(2048)->nullable(),
                                ]),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Sort')
                    ->sortable(),
                TextColumn::make('item_type')
                    ->label('Slide type')
                    ->badge()
                    ->sortable(),
                ViewColumn::make('preview')
                    ->label('Preview')
                    ->view('filament.story-slide-thumb'),
                TextColumn::make('item_duration_seconds')
                    ->label('Display seconds')
                    ->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable()->sinceTooltip(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => RestaurantStoryResource::canEdit($this->getOwnerRecord())),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => RestaurantStoryResource::canEdit($this->getOwnerRecord())),
                DeleteAction::make()
                    ->visible(fn (): bool => RestaurantStoryResource::canEdit($this->getOwnerRecord())),
            ])
            ->bulkActions([])
            ->defaultSort('sort_order');
    }
}

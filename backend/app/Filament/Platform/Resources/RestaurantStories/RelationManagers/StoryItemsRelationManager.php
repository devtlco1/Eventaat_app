<?php

namespace App\Filament\Platform\Resources\RestaurantStories\RelationManagers;

use App\Filament\Platform\Resources\RestaurantStories\RestaurantStoryResource;
use App\Models\RestaurantStoryItem;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StoryItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Story items';

    protected static bool $shouldSkipAuthorization = true;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('item_type')
                    ->label('Item type')
                    ->required()
                    ->options(array_combine(RestaurantStoryItem::ITEM_TYPES, RestaurantStoryItem::ITEM_TYPES))
                    ->default(RestaurantStoryItem::TYPE_IMAGE)
                    ->reactive(),

                FileUpload::make('media_path')
                    ->label('Media')
                    ->disk('public')
                    ->directory(fn (): string => 'stories/'.$this->getOwnerRecord()->getKey())
                    ->visibility('public')
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
                    }),

                Textarea::make('body')
                    ->label('Text body')
                    ->rows(6)
                    ->nullable()
                    ->visible(fn (Get $get): bool => $get('item_type') === RestaurantStoryItem::TYPE_TEXT),

                TextInput::make('cta_label')->label('CTA label')->maxLength(255)->nullable(),
                TextInput::make('cta_url')->label('CTA URL')->maxLength(2048)->nullable(),
                TextInput::make('sort_order')->numeric()->required()->default(0),
                TextInput::make('item_duration_seconds')
                    ->label('Duration (seconds)')
                    ->numeric()
                    ->minValue(1)
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->sortable(),
                TextColumn::make('item_type')->badge()->sortable(),
                ImageColumn::make('media_path')
                    ->disk('public')
                    ->square()
                    ->height(56)
                    ->visible(fn (?RestaurantStoryItem $record): bool => $record !== null
                        && $record->item_type === RestaurantStoryItem::TYPE_IMAGE
                        && filled($record->media_path)),
                TextColumn::make('media_path')
                    ->label('Media')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('body')->limit(60)->wrap(),
                TextColumn::make('item_duration_seconds')->label('Seconds')->placeholder('—'),
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

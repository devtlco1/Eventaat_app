<?php

namespace App\Filament\Restaurant\Resources\RestaurantMenus\RelationManagers;

use App\Filament\Restaurant\Resources\RestaurantMenus\RestaurantMenuCategoryResource;
use App\Filament\Restaurant\Resources\RestaurantMenus\RestaurantMenuResource;
use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuItem;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RestaurantMenuCategoryItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Items';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (! $ownerRecord instanceof RestaurantMenuCategory) {
            return false;
        }

        $menu = $ownerRecord->relationLoaded('menu')
            ? $ownerRecord->menu
            : $ownerRecord->menu()->first();

        if (! $menu || ! $menu->isStructured()) {
            return false;
        }

        return RestaurantMenuCategoryResource::canView($ownerRecord);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->compact()
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Image')
                            ->image()
                            ->imagePreviewHeight('10rem')
                            ->disk('public')
                            ->directory('menus/items')
                            ->visibility('public')
                            ->maxFiles(1)
                            ->fetchFileInformation(false)
                            ->nullable()
                            ->downloadable(false)
                            ->openable()
                            ->columnSpanFull(),
                        Grid::make(2)->schema([
                            TextInput::make('name')->required()->maxLength(255),
                            TextInput::make('price')->numeric()->minValue(0)->nullable(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('currency')->default('IQD')->maxLength(8)->required(),
                            TextInput::make('display_order')
                                ->label('Display order')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                        ]),
                        Grid::make(2)->schema([
                            Toggle::make('is_available')->label('Available')->default(true),
                            Toggle::make('is_featured')->label('Featured')->default(false),
                        ]),
                        Textarea::make('description')->rows(3)->nullable()->columnSpanFull(),
                        Textarea::make('notes')->label('Internal notes')->rows(2)->nullable()->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->striped()
            ->emptyStateHeading('No items yet')
            ->emptyStateDescription('Add the first item for this category.')
            ->emptyStateIcon(Heroicon::OutlinedPhoto)
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Image')
                    ->disk('public')
                    ->square()
                    ->imageHeight(44)
                    ->imageWidth(44)
                    ->checkFileExistence(false)
                    ->visibility('public')
                    ->getStateUsing(fn (RestaurantMenuItem $record): ?string => RestaurantMenuItem::normalizeStoredImagePath($record->image_path))
                    ->placeholder('—'),
                TextColumn::make('name')->label('Name')->searchable()->sortable()->wrap(),
                TextColumn::make('description')
                    ->label('Description')
                    ->placeholder('—')
                    ->limit(60)
                    ->tooltip(fn (RestaurantMenuItem $record): ?string => filled($record->description) ? $record->description : null)
                    ->wrap(),
                TextColumn::make('price')
                    ->label('Price')
                    ->placeholder('—')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => $state === null || $state === ''
                        ? '—'
                        : number_format((float) $state, 2)),
                TextColumn::make('currency')->label('Currency')->badge(),
                IconColumn::make('is_available')->label('Available')->boolean(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                TextColumn::make('display_order')->label('Order')->sortable()->alignCenter(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add item')
                    ->modalHeading('Add item')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->visible(fn (): bool => RestaurantMenuResource::canEdit($this->getOwnerRecord()->menu)),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->modalHeading('Edit item')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->visible(fn (): bool => RestaurantMenuResource::canEdit($this->getOwnerRecord()->menu)),
                DeleteAction::make()
                    ->label('Delete')
                    ->modalWidth(Width::Medium)
                    ->visible(fn (): bool => RestaurantMenuResource::canEdit($this->getOwnerRecord()->menu)),
            ])
            ->bulkActions([])
            ->defaultSort('display_order');
    }
}

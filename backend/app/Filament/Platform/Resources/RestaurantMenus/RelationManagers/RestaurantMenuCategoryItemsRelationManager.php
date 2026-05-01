<?php

namespace App\Filament\Platform\Resources\RestaurantMenus\RelationManagers;

use App\Filament\Platform\Resources\RestaurantMenus\RestaurantMenuCategoryResource;
use App\Filament\Platform\Resources\RestaurantMenus\RestaurantMenuResource;
use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuItem;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
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
                Section::make()->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    Textarea::make('description')->rows(3)->nullable(),
                    TextInput::make('price')->numeric()->minValue(0)->nullable(),
                    TextInput::make('currency')->default('IQD')->maxLength(8)->required(),
                    Toggle::make('is_available')->label('Available')->default(true),
                    Toggle::make('is_featured')->label('Featured')->default(false),
                    TextInput::make('display_order')
                        ->label('Display order')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                    Textarea::make('notes')->rows(2)->nullable(),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_order')->label('Order')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('price')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state, RestaurantMenuItem $record): string => $state === null || $state === ''
                        ? '—'
                        : number_format((float) $state, 2).' '.$record->currency),
                TextColumn::make('currency')->badge()->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_available')->label('Avail.')->boolean(),
                IconColumn::make('is_featured')->label('Feat.')->boolean(),
                TextColumn::make('updated_at')->dateTime()->sortable()->sinceTooltip(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => RestaurantMenuResource::canEdit($this->getOwnerRecord()->menu)),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => RestaurantMenuResource::canEdit($this->getOwnerRecord()->menu)),
                DeleteAction::make()
                    ->visible(fn (): bool => RestaurantMenuResource::canEdit($this->getOwnerRecord()->menu)),
            ])
            ->bulkActions([])
            ->defaultSort('display_order');
    }
}

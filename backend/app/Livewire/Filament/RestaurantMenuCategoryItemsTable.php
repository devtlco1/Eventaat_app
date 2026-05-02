<?php

namespace App\Livewire\Filament;

use App\Filament\Platform\Resources\RestaurantMenus\RestaurantMenuResource as PlatformRestaurantMenuResource;
use App\Filament\Restaurant\Resources\RestaurantMenus\RestaurantMenuResource as RestaurantRestaurantMenuResource;
use App\Models\RestaurantMenu;
use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuItem;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RestaurantMenuCategoryItemsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    #[Locked]
    public int $categoryId;

    public function mount(int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof CreateAction, $action instanceof EditAction => fn (Schema $schema): Schema => $this->form($schema),
            default => null,
        };
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
                            TextInput::make('name')
                                ->label('Item name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('price')
                                ->label('Price')
                                ->numeric()
                                ->minValue(0)
                                ->nullable(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('currency')
                                ->label('Currency')
                                ->default('IQD')
                                ->maxLength(8)
                                ->required(),
                            TextInput::make('display_order')
                                ->label('Display order')
                                ->numeric()
                                ->default(fn (): int => (int) ((RestaurantMenuItem::query()
                                    ->where('restaurant_menu_category_id', $this->categoryId)
                                    ->max('display_order')) ?? -1) + 1)
                                ->minValue(0),
                        ]),
                        Grid::make(2)->schema([
                            Toggle::make('is_available')
                                ->label('Available')
                                ->default(true),
                            Toggle::make('is_featured')
                                ->label('Featured')
                                ->default(false),
                        ]),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Internal notes')
                            ->rows(2)
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->paginated(false)
            ->defaultSort('display_order')
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
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('description')
                    ->label('Description')
                    ->placeholder('—')
                    ->limit(60)
                    ->tooltip(fn (RestaurantMenuItem $record): ?string => filled($record->description) ? $record->description : null)
                    ->wrap(),
                TextColumn::make('price')
                    ->label('Price')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(fn ($state): string => $state === null || $state === ''
                        ? '—'
                        : number_format((float) $state, 2)),
                TextColumn::make('currency')
                    ->label('Currency')
                    ->badge(),
                IconColumn::make('is_available')
                    ->label('Available')
                    ->boolean(),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
                TextColumn::make('display_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add item')
                    ->modalHeading('Add item')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->model(RestaurantMenuItem::class)
                    ->visible(fn (): bool => $this->canEditMenu())
                    ->mutateFormDataUsing(fn (array $data): array => [
                        ...$data,
                        'restaurant_menu_category_id' => $this->categoryId,
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->modalHeading('Edit item')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->visible(fn (): bool => $this->canEditMenu()),
                DeleteAction::make()
                    ->label('Delete')
                    ->modalWidth(Width::Medium)
                    ->visible(fn (): bool => $this->canEditMenu()),
            ])
            ->bulkActions([]);
    }

    protected function getTableQuery(): Builder
    {
        return RestaurantMenuItem::query()
            ->where('restaurant_menu_category_id', $this->categoryId);
    }

    protected function resolveMenu(): ?RestaurantMenu
    {
        $category = RestaurantMenuCategory::query()
            ->with('menu')
            ->find($this->categoryId);

        return $category?->menu;
    }

    protected function canEditMenu(): bool
    {
        $menu = $this->resolveMenu();

        if (! $menu || ! $menu->isStructured()) {
            return false;
        }

        return match (Filament::getCurrentPanel()?->getId()) {
            'platform' => PlatformRestaurantMenuResource::canEdit($menu),
            'restaurant' => RestaurantRestaurantMenuResource::canEdit($menu),
            default => false,
        };
    }

    public function render(): View
    {
        return view('livewire.filament.restaurant-menu-category-items-table');
    }
}

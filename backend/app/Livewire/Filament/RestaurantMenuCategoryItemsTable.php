<?php

namespace App\Livewire\Filament;

use App\Filament\Platform\Resources\RestaurantMenus\RestaurantMenuResource as PlatformRestaurantMenuResource;
use App\Filament\Restaurant\Resources\RestaurantMenus\RestaurantMenuResource as RestaurantRestaurantMenuResource;
use App\Filament\Support\RestaurantMenuItemFormSchema;
use App\Models\RestaurantMenu;
use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuItem;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
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
            $action instanceof EditAction => fn (Schema $schema): Schema => $this->form($schema),
            default => null,
        };
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(RestaurantMenuItemFormSchema::sections($this->categoryId));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->paginated(false)
            ->defaultSort('display_order')
            ->striped()
            ->searchable(false)
            ->emptyStateHeading('No items yet')
            ->emptyStateDescription('Use Add item to create the first item.')
            ->emptyStateIcon(null)
            ->columns([
                ViewColumn::make('image_thumb')
                    ->label('Image')
                    ->view('filament.tables.columns.restaurant-menu-item-thumb'),
                TextColumn::make('name')
                    ->label('Name')
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

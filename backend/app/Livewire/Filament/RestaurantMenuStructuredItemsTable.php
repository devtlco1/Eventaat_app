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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class RestaurantMenuStructuredItemsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    #[Locked]
    public int $menuId;

    public function mount(int $menuId): void
    {
        $this->menuId = $menuId;
    }

    #[On('menu-structure-changed')]
    public function refreshStructuredItems(): void
    {
        $this->resetTable();
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof EditAction => fn (Schema $schema): Schema => $schema->components(
                RestaurantMenuItemFormSchema::sectionsForMenu($this->menuId),
            ),
            default => null,
        };
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->tableQuery())
            ->paginated(false)
            ->striped()
            ->searchable(false)
            ->emptyStateHeading('No menu items yet.')
            ->emptyStateDescription('Use Add item to create the first item.')
            ->emptyStateIcon(null)
            ->columns([
                ViewColumn::make('image_thumb')
                    ->label('Image')
                    ->view('filament.tables.columns.restaurant-menu-item-thumb'),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->placeholder('—'),
                TextColumn::make('name')
                    ->label('Name')
                    ->wrap(),
                TextColumn::make('description')
                    ->label('Description')
                    ->placeholder('—')
                    ->limit(60)
                    ->tooltip(fn (RestaurantMenuItem $record): ?string => filled($record->description) ? $record->description : null)
                    ->wrap(),
                TextColumn::make('price')
                    ->label('Price')
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
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('restaurant_menu_category_id')
                    ->label('Category')
                    ->options(fn (): array => RestaurantMenuCategory::query()
                        ->where('restaurant_menu_id', $this->menuId)
                        ->orderBy('display_order')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->modalHeading('Edit item')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->visible(fn (): bool => $this->canManage())
                    ->after(fn () => $this->dispatch('menu-structure-changed')),
                DeleteAction::make()
                    ->label('Delete')
                    ->modalWidth(Width::Medium)
                    ->requiresConfirmation()
                    ->visible(fn (): bool => $this->canManage())
                    ->after(fn () => $this->dispatch('menu-structure-changed')),
            ])
            ->bulkActions([]);
    }

    protected function tableQuery(): Builder
    {
        return RestaurantMenuItem::query()
            ->select('restaurant_menu_items.*')
            ->join(
                'restaurant_menu_categories',
                'restaurant_menu_items.restaurant_menu_category_id',
                '=',
                'restaurant_menu_categories.id',
            )
            ->where('restaurant_menu_categories.restaurant_menu_id', $this->menuId)
            ->with(['category'])
            ->orderBy('restaurant_menu_categories.display_order')
            ->orderBy('restaurant_menu_categories.name')
            ->orderBy('restaurant_menu_items.display_order')
            ->orderBy('restaurant_menu_items.name');
    }

    protected function resolveMenu(): ?RestaurantMenu
    {
        return RestaurantMenu::query()->find($this->menuId);
    }

    protected function canManage(): bool
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
        return view('livewire.filament.restaurant-menu-structured-items-table');
    }
}

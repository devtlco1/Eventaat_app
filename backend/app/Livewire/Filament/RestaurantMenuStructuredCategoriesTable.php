<?php

namespace App\Livewire\Filament;

use App\Filament\Platform\Resources\RestaurantMenus\RestaurantMenuResource as PlatformRestaurantMenuResource;
use App\Filament\Restaurant\Resources\RestaurantMenus\RestaurantMenuResource as RestaurantRestaurantMenuResource;
use App\Filament\Support\FilamentSchemaLayout;
use App\Filament\Support\RestaurantMenuCategoryFormSchema;
use App\Models\RestaurantMenu;
use App\Models\RestaurantMenuCategory;
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
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class RestaurantMenuStructuredCategoriesTable extends Component implements HasActions, HasSchemas, HasTable
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
    public function refreshStructuredCategories(): void
    {
        $this->resetTable();
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof EditAction => fn (Schema $schema): Schema => FilamentSchemaLayout::stackSections($schema)->components(RestaurantMenuCategoryFormSchema::sections()),
            default => null,
        };
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->tableQuery())
            ->paginated(false)
            ->defaultSort('display_order')
            ->striped()
            ->searchable(false)
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->weight('medium'),
                TextColumn::make('display_order')
                    ->label('Order')
                    ->alignCenter(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->modalHeading('Edit category')
                    ->modalWidth(Width::Large)
                    ->visible(fn (): bool => $this->canManage())
                    ->after(fn () => $this->dispatch('menu-structure-changed')),
                DeleteAction::make()
                    ->label('Delete')
                    ->modalWidth(Width::Medium)
                    ->requiresConfirmation()
                    ->modalDescription('This will permanently delete the category and all of its menu items.')
                    ->visible(fn (): bool => $this->canManage())
                    ->after(fn () => $this->dispatch('menu-structure-changed')),
            ])
            ->bulkActions([]);
    }

    protected function tableQuery(): Builder
    {
        return RestaurantMenuCategory::query()
            ->where('restaurant_menu_id', $this->menuId)
            ->orderBy('display_order')
            ->orderBy('name');
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
        return view('livewire.filament.restaurant-menu-structured-categories-table');
    }
}

<?php

namespace App\Livewire\Filament;

use App\Filament\Platform\Resources\RestaurantMenus\RestaurantMenuResource as PlatformRestaurantMenuResource;
use App\Filament\Restaurant\Resources\RestaurantMenus\RestaurantMenuResource as RestaurantRestaurantMenuResource;
use App\Filament\Support\RestaurantMenuCategoryFormSchema;
use App\Filament\Support\RestaurantMenuItemFormSchema;
use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuItem;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RestaurantMenuStructuredContent extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    #[Locked]
    public Model $record;

    public function boot(): void
    {
        $this->bootedInteractsWithActions();
    }

    public function render()
    {
        return view('livewire.filament.restaurant-menu-structured-content');
    }

    protected function canManage(): bool
    {
        return match (Filament::getCurrentPanel()?->getId()) {
            'platform' => PlatformRestaurantMenuResource::canEdit($this->record),
            'restaurant' => RestaurantRestaurantMenuResource::canEdit($this->record),
            default => false,
        };
    }

    public function createCategoryAction(): Action
    {
        return Action::make('createCategory')
            ->label('Add category')
            ->icon(Heroicon::OutlinedPlus)
            ->modalHeading('Add category')
            ->modalSubmitActionLabel('Create')
            ->modalWidth(Width::Large)
            ->visible(fn (): bool => $this->canManage())
            ->schema(RestaurantMenuCategoryFormSchema::sections())
            ->fillForm(fn (): array => [
                'name' => '',
                'description' => null,
                'display_order' => (int) ((RestaurantMenuCategory::query()
                    ->where('restaurant_menu_id', $this->record->getKey())
                    ->max('display_order')) ?? -1) + 1,
                'is_active' => true,
            ])
            ->action(function (array $data): void {
                RestaurantMenuCategory::create([
                    'restaurant_menu_id' => (int) $this->record->getKey(),
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'display_order' => (int) ($data['display_order'] ?? 0),
                    'is_active' => (bool) ($data['is_active'] ?? true),
                ]);
                $this->record->refresh();
                $this->dispatch('menu-structure-changed');
            })
            ->successNotificationTitle('Category created');
    }

    public function createItemAction(): Action
    {
        $menuId = (int) $this->record->getKey();

        return Action::make('createItem')
            ->label('Add item')
            ->icon(Heroicon::OutlinedPlus)
            ->modalHeading('Add item')
            ->modalSubmitActionLabel('Create')
            ->modalWidth(Width::FiveExtraLarge)
            ->visible(fn (): bool => $this->canManage())
            ->schema(RestaurantMenuItemFormSchema::sectionsForMenu($menuId))
            ->fillForm(fn (): array => [
                'restaurant_menu_category_id' => null,
                'name' => '',
                'description' => null,
                'image_path' => null,
                'price' => null,
                'currency' => 'IQD',
                'is_available' => true,
                'is_featured' => false,
                'display_order' => 0,
                'notes' => null,
            ])
            ->action(function (array $data) use ($menuId): void {
                $cid = (int) ($data['restaurant_menu_category_id'] ?? 0);
                abort_unless(
                    RestaurantMenuCategory::query()->whereKey($cid)->where('restaurant_menu_id', $menuId)->exists(),
                    403,
                );

                RestaurantMenuItem::create([
                    'restaurant_menu_category_id' => $cid,
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'image_path' => $data['image_path'] ?? null,
                    'price' => $data['price'] ?? null,
                    'currency' => $data['currency'] ?? 'IQD',
                    'is_available' => (bool) ($data['is_available'] ?? true),
                    'is_featured' => (bool) ($data['is_featured'] ?? false),
                    'display_order' => (int) ($data['display_order'] ?? 0),
                    'notes' => $data['notes'] ?? null,
                ]);

                $this->record->refresh();
                $this->dispatch('menu-structure-changed');
            })
            ->successNotificationTitle('Item created');
    }
}

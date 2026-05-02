<?php

namespace App\Livewire\Filament;

use App\Filament\Platform\Resources\RestaurantMenus\RestaurantMenuResource as PlatformRestaurantMenuResource;
use App\Filament\Restaurant\Resources\RestaurantMenus\RestaurantMenuResource as RestaurantRestaurantMenuResource;
use App\Filament\Support\RestaurantMenuItemFormSchema;
use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuItem;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RestaurantMenuBuilder extends Component implements HasActions, HasSchemas
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
        $this->record->loadMissing([
            'categories' => fn ($q) => $q->orderBy('display_order'),
        ]);

        return view('livewire.filament.restaurant-menu-builder');
    }

    public function canManage(): bool
    {
        return match (Filament::getCurrentPanel()?->getId()) {
            'platform' => PlatformRestaurantMenuResource::canEdit($this->record),
            'restaurant' => RestaurantRestaurantMenuResource::canEdit($this->record),
            default => false,
        };
    }

    /**
     * @return array<int, Section>
     */
    protected function categoryFormSchema(): array
    {
        return [
            Section::make()
                ->compact()
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->nullable(),
                    TextInput::make('display_order')
                        ->label('Display order')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]),
        ];
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
            ->schema($this->categoryFormSchema())
            ->fillForm([
                'name' => '',
                'description' => null,
                'display_order' => (int) (($this->record->categories()->max('display_order')) ?? -1) + 1,
                'is_active' => true,
            ])
            ->action(function (array $data): void {
                $this->record->categories()->create([
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'display_order' => (int) ($data['display_order'] ?? 0),
                    'is_active' => (bool) ($data['is_active'] ?? true),
                ]);
                $this->record->refresh();
            })
            ->successNotificationTitle('Category created');
    }

    public function editCategoryAction(): Action
    {
        return Action::make('editCategory')
            ->label('Edit category')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->modalHeading('Edit category')
            ->modalSubmitActionLabel('Save')
            ->modalWidth(Width::Large)
            ->visible(fn (): bool => $this->canManage())
            ->schema($this->categoryFormSchema())
            ->fillForm(function (array $arguments): array {
                $category = RestaurantMenuCategory::query()->findOrFail($arguments['category']);

                return [
                    'name' => $category->name,
                    'description' => $category->description,
                    'display_order' => $category->display_order,
                    'is_active' => $category->is_active,
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $category = RestaurantMenuCategory::query()->findOrFail($arguments['category']);
                abort_unless((int) $category->restaurant_menu_id === (int) $this->record->id, 403);
                $category->update([
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'display_order' => (int) ($data['display_order'] ?? 0),
                    'is_active' => (bool) ($data['is_active'] ?? true),
                ]);
                $this->record->refresh();
            })
            ->successNotificationTitle('Category saved');
    }

    public function deleteCategoryAction(): Action
    {
        return Action::make('deleteCategory')
            ->label('Delete category')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->visible(fn (): bool => $this->canManage())
            ->requiresConfirmation()
            ->modalHeading('Delete category')
            ->modalWidth(Width::Medium)
            ->modalDescription('This will permanently delete the category and all of its menu items.')
            ->action(function (array $arguments): void {
                $category = RestaurantMenuCategory::query()->findOrFail($arguments['category']);
                abort_unless((int) $category->restaurant_menu_id === (int) $this->record->id, 403);
                $category->delete();
                $this->record->refresh();
            })
            ->successNotificationTitle('Category deleted');
    }

    public function createItemAction(): Action
    {
        return Action::make('createItem')
            ->label('Add item')
            ->icon(Heroicon::OutlinedPlus)
            ->modalHeading('Add item')
            ->modalSubmitActionLabel('Create')
            ->modalWidth(Width::FiveExtraLarge)
            ->visible(fn (): bool => $this->canManage())
            ->schema(function (array $arguments): array {
                $categoryId = (int) ($arguments['category'] ?? 0);
                $category = RestaurantMenuCategory::query()->findOrFail($categoryId);
                abort_unless((int) $category->restaurant_menu_id === (int) $this->record->id, 403);

                return RestaurantMenuItemFormSchema::sections($categoryId);
            })
            ->fillForm(function (array $arguments): array {
                $categoryId = (int) ($arguments['category'] ?? 0);
                $category = RestaurantMenuCategory::query()->findOrFail($categoryId);
                abort_unless((int) $category->restaurant_menu_id === (int) $this->record->id, 403);

                return [
                    'name' => '',
                    'description' => null,
                    'image_path' => null,
                    'price' => null,
                    'currency' => 'IQD',
                    'is_available' => true,
                    'is_featured' => false,
                    'display_order' => (int) ((RestaurantMenuItem::query()
                        ->where('restaurant_menu_category_id', $categoryId)
                        ->max('display_order')) ?? -1) + 1,
                    'notes' => null,
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $categoryId = (int) ($arguments['category'] ?? 0);
                $category = RestaurantMenuCategory::query()->findOrFail($categoryId);
                abort_unless((int) $category->restaurant_menu_id === (int) $this->record->id, 403);

                RestaurantMenuItem::create([
                    'restaurant_menu_category_id' => $categoryId,
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
            })
            ->successNotificationTitle('Item created');
    }
}

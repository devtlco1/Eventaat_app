<?php

namespace App\Livewire\Filament;

use App\Filament\Platform\Resources\RestaurantMenus\RestaurantMenuResource as PlatformRestaurantMenuResource;
use App\Filament\Restaurant\Resources\RestaurantMenus\RestaurantMenuResource as RestaurantRestaurantMenuResource;
use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuItem;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
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
        $this->record->loadMissing(['categories' => fn ($q) => $q->orderBy('display_order'), 'categories.items' => fn ($q) => $q->orderBy('display_order')]);

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
     * @return array<int, \Filament\Forms\Components\Component>
     */
    protected function categoryFormComponents(): array
    {
        return [
            TextInput::make('name')
                ->label('Category name')
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
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    protected function itemFormComponents(): array
    {
        return [
            FileUpload::make('image_path')
                ->label('Image')
                ->disk('public')
                ->directory('menus/items')
                ->visibility('public')
                ->acceptedFileTypes(['image/*'])
                ->maxFiles(1)
                ->nullable()
                ->downloadable(false),
            TextInput::make('name')
                ->label('Item name')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->nullable(),
            Grid::make(2)->schema([
                TextInput::make('price')
                    ->label('Price')
                    ->numeric()
                    ->minValue(0)
                    ->nullable(),
                TextInput::make('currency')
                    ->label('Currency')
                    ->default('IQD')
                    ->maxLength(8)
                    ->required(),
            ]),
            Grid::make(2)->schema([
                Toggle::make('is_available')
                    ->label('Available')
                    ->default(true),
                Toggle::make('is_featured')
                    ->label('Featured')
                    ->default(false),
            ]),
            TextInput::make('display_order')
                ->label('Display order')
                ->numeric()
                ->default(0)
                ->minValue(0),
            Textarea::make('notes')
                ->label('Internal notes')
                ->rows(2)
                ->nullable(),
        ];
    }

    public function createCategoryAction(): Action
    {
        return Action::make('createCategory')
            ->label('Add category')
            ->icon(Heroicon::OutlinedPlus)
            ->modalHeading('Add category')
            ->modalSubmitActionLabel('Create')
            ->visible(fn (): bool => $this->canManage())
            ->schema($this->categoryFormComponents())
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
            ->label('Edit')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->modalHeading('Edit category')
            ->modalSubmitActionLabel('Save')
            ->visible(fn (): bool => $this->canManage())
            ->schema($this->categoryFormComponents())
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
            ->label('Delete')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->visible(fn (): bool => $this->canManage())
            ->requiresConfirmation()
            ->modalHeading('Delete category')
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
            ->modalHeading('Add menu item')
            ->modalSubmitActionLabel('Create')
            ->visible(fn (): bool => $this->canManage())
            ->schema($this->itemFormComponents())
            ->fillForm(function (array $arguments): array {
                $category = RestaurantMenuCategory::query()->findOrFail($arguments['category']);
                abort_unless((int) $category->restaurant_menu_id === (int) $this->record->id, 403);

                return [
                    'image_path' => null,
                    'name' => '',
                    'description' => null,
                    'price' => null,
                    'currency' => 'IQD',
                    'is_available' => true,
                    'is_featured' => false,
                    'display_order' => (int) (($category->items()->max('display_order')) ?? -1) + 1,
                    'notes' => null,
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $category = RestaurantMenuCategory::query()->findOrFail($arguments['category']);
                abort_unless((int) $category->restaurant_menu_id === (int) $this->record->id, 403);
                $category->items()->create([
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

    public function editItemAction(): Action
    {
        return Action::make('editItem')
            ->label('Edit')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->modalHeading('Edit menu item')
            ->modalSubmitActionLabel('Save')
            ->visible(fn (): bool => $this->canManage())
            ->schema($this->itemFormComponents())
            ->fillForm(function (array $arguments): array {
                $item = RestaurantMenuItem::query()->findOrFail($arguments['item']);
                $category = $item->category;
                abort_unless($category && (int) $category->restaurant_menu_id === (int) $this->record->id, 403);

                return [
                    'image_path' => $item->image_path,
                    'name' => $item->name,
                    'description' => $item->description,
                    'price' => $item->price,
                    'currency' => $item->currency,
                    'is_available' => $item->is_available,
                    'is_featured' => $item->is_featured,
                    'display_order' => $item->display_order,
                    'notes' => $item->notes,
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $item = RestaurantMenuItem::query()->findOrFail($arguments['item']);
                $category = $item->category;
                abort_unless($category && (int) $category->restaurant_menu_id === (int) $this->record->id, 403);
                $item->update([
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
            ->successNotificationTitle('Item saved');
    }

    public function deleteItemAction(): Action
    {
        return Action::make('deleteItem')
            ->label('Delete')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->visible(fn (): bool => $this->canManage())
            ->requiresConfirmation()
            ->modalHeading('Delete menu item')
            ->action(function (array $arguments): void {
                $item = RestaurantMenuItem::query()->findOrFail($arguments['item']);
                $category = $item->category;
                abort_unless($category && (int) $category->restaurant_menu_id === (int) $this->record->id, 403);
                $item->delete();
                $this->record->refresh();
            })
            ->successNotificationTitle('Item deleted');
    }
}

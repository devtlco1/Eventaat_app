<div
    class="fi-section-content space-y-6"
    wire:key="restaurant-menu-structured-content-{{ $record->getKey() }}"
>
    @if ($record->isStructured())
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-sm font-semibold leading-6 text-gray-950 dark:text-white">
                Menu builder
            </h3>
            @if ($this->canManage())
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                    {{ $this->createCategoryAction }}
                    {{ $this->createItemAction }}
                </div>
            @endif
        </div>

        <div class="space-y-8">
            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-gray-950 dark:text-white">Categories</h4>
                @livewire(
                    \App\Livewire\Filament\RestaurantMenuStructuredCategoriesTable::class,
                    ['menuId' => $record->getKey()],
                    key('structured-menu-categories-' . $record->getKey()),
                )
            </div>

            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-gray-950 dark:text-white">Items</h4>
                @livewire(
                    \App\Livewire\Filament\RestaurantMenuStructuredItemsTable::class,
                    ['menuId' => $record->getKey()],
                    key('structured-menu-items-' . $record->getKey()),
                )
            </div>
        </div>

        <x-filament-actions::modals />
    @endif
</div>

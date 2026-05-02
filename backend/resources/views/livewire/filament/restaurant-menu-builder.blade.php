@php
    use Illuminate\Support\Js;
@endphp

<div class="fi-section-content space-y-6" wire:key="restaurant-menu-builder-{{ $record->getKey() }}">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 flex-1 space-y-1">
            <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Menu builder
            </h3>
            <p class="max-w-3xl text-sm text-gray-600 dark:text-gray-400">
                Manage categories and items for this structured menu.
            </p>
        </div>
        @if ($this->canManage())
            <div class="flex shrink-0 justify-end">
                {{ $this->createCategoryAction }}
            </div>
        @endif
    </div>

    @forelse ($record->categories as $category)
        <section
            class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-950 dark:ring-white/10"
        >
            <header
                class="border-b border-gray-200 bg-gray-50 px-4 py-4 dark:border-white/10 dark:bg-white/[0.03] sm:px-6 sm:py-4"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between lg:gap-6">
                    <div class="min-w-0 flex-1 space-y-3">
                        <h4 class="text-base font-semibold leading-snug text-gray-950 dark:text-white">
                            {{ $category->name }}
                        </h4>
                        @if (filled($category->description))
                            <p class="max-w-4xl text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                                {{ $category->description }}
                            </p>
                        @endif
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($category->is_active)
                                <span
                                    class="fi-badge fi-size-sm inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset fi-color-success bg-success-50 text-success-700 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/20"
                                >
                                    Active
                                </span>
                            @else
                                <span
                                    class="fi-badge fi-size-sm inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset fi-color-gray bg-gray-50 text-gray-700 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20"
                                >
                                    Inactive
                                </span>
                            @endif
                            <span
                                class="fi-badge fi-size-sm inline-flex items-center gap-x-1 rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-600/10 ring-inset dark:bg-white/10 dark:text-gray-300 dark:ring-white/10"
                            >
                                Order #{{ $category->display_order }}
                            </span>
                        </div>
                    </div>

                    @if ($this->canManage())
                        <div
                            class="flex shrink-0 flex-wrap items-center gap-2 lg:flex-nowrap lg:justify-end lg:pt-0.5"
                        >
                            <x-filament::button
                                size="sm"
                                color="gray"
                                outlined
                                type="button"
                                wire:click="mountAction('editCategory', {{ Js::from(['category' => $category->id]) }})"
                            >
                                Edit category
                            </x-filament::button>
                            <x-filament::button
                                size="sm"
                                color="danger"
                                outlined
                                type="button"
                                wire:click="mountAction('deleteCategory', {{ Js::from(['category' => $category->id]) }})"
                            >
                                Delete category
                            </x-filament::button>
                            <x-filament::button
                                size="sm"
                                color="primary"
                                type="button"
                                wire:click="mountAction('createItem', {{ Js::from(['category' => $category->id]) }})"
                            >
                                Add item
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </header>

            <div class="fi-section-content-ctn bg-white dark:bg-gray-950">
                @livewire(
                    \App\Livewire\Filament\RestaurantMenuCategoryItemsTable::class,
                    ['categoryId' => $category->id],
                    key('restaurant-menu-category-items-' . $record->getKey() . '-' . $category->id)
                )
            </div>
        </section>
    @empty
        <div
            class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center dark:border-white/10 dark:bg-white/5"
        >
            <p class="text-sm font-medium text-gray-950 dark:text-white">No categories yet</p>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                @if ($this->canManage())
                    Use “Add category” to create your first section.
                @else
                    Categories will appear here once they are added.
                @endif
            </p>
        </div>
    @endforelse

    <x-filament-actions::modals />
</div>

@php
    use Illuminate\Support\Js;
@endphp

<div class="space-y-6" wire:key="restaurant-menu-builder-{{ $record->getKey() }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Menu builder
            </h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage categories and items. Changes save immediately.
            </p>
        </div>
        @if ($this->canManage())
            <div class="flex shrink-0">
                {{ $this->createCategoryAction }}
            </div>
        @endif
    </div>

    @forelse ($record->categories as $category)
        <div
            class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        >
            <div
                class="flex flex-col gap-3 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-white/10"
            >
                <div class="min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h4 class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $category->name }}
                        </h4>
                        @if ($category->is_active)
                            <span
                                class="fi-badge fi-size-xs items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset fi-color-success bg-success-50 text-success-700 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/20"
                            >
                                Active
                            </span>
                        @else
                            <span
                                class="fi-badge fi-size-xs items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset fi-color-gray bg-gray-50 text-gray-700 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20"
                            >
                                Inactive
                            </span>
                        @endif
                    </div>
                    @if (filled($category->description))
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $category->description }}
                        </p>
                    @endif
                    <p class="text-xs text-gray-500 dark:text-gray-500">
                        Display order: {{ $category->display_order }}
                    </p>
                </div>
                @if ($this->canManage())
                    <div class="flex flex-wrap items-center gap-2">
                        <x-filament::button
                            size="xs"
                            color="gray"
                            outlined
                            type="button"
                            wire:click="mountAction('editCategory', {{ Js::from(['category' => $category->id]) }})"
                        >
                            Edit category
                        </x-filament::button>
                        <x-filament::button
                            size="xs"
                            color="danger"
                            outlined
                            type="button"
                            wire:click="mountAction('deleteCategory', {{ Js::from(['category' => $category->id]) }})"
                        >
                            Delete category
                        </x-filament::button>
                    </div>
                @endif
            </div>

            <div class="p-0">
                @livewire(
                    \App\Livewire\Filament\RestaurantMenuCategoryItemsTable::class,
                    ['categoryId' => $category->id],
                    key('restaurant-menu-category-items-' . $record->getKey() . '-' . $category->id)
                )
            </div>
        </div>
    @empty
        <div
            class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
        >
            No categories yet. @if ($this->canManage())Use “Add category” to get started.@endif
        </div>
    @endforelse

    <x-filament-actions::modals />
</div>

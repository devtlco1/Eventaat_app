@php
    use Illuminate\Support\Facades\Storage;
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
                            wire:click="mountAction('editCategory', @js(['category' => $category->id]))"
                        >
                            Edit category
                        </x-filament::button>
                        <x-filament::button
                            size="xs"
                            color="danger"
                            outlined
                            type="button"
                            wire:click="mountAction('deleteCategory', @js(['category' => $category->id]))"
                        >
                            Delete category
                        </x-filament::button>
                        <x-filament::button
                            size="xs"
                            color="primary"
                            type="button"
                            wire:click="mountAction('createItem', @js(['category' => $category->id]))"
                        >
                            Add item
                        </x-filament::button>
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[56rem] divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-3 py-2 text-start text-xs font-medium text-gray-600 dark:text-gray-400">
                                Image
                            </th>
                            <th class="px-3 py-2 text-start text-xs font-medium text-gray-600 dark:text-gray-400">
                                Item
                            </th>
                            <th class="px-3 py-2 text-start text-xs font-medium text-gray-600 dark:text-gray-400">
                                Description
                            </th>
                            <th class="px-3 py-2 text-start text-xs font-medium text-gray-600 dark:text-gray-400">
                                Price
                            </th>
                            <th class="px-3 py-2 text-start text-xs font-medium text-gray-600 dark:text-gray-400">
                                Available
                            </th>
                            <th class="px-3 py-2 text-start text-xs font-medium text-gray-600 dark:text-gray-400">
                                Featured
                            </th>
                            <th class="px-3 py-2 text-start text-xs font-medium text-gray-600 dark:text-gray-400">
                                Order
                            </th>
                            @if ($this->canManage())
                                <th class="px-3 py-2 text-end text-xs font-medium text-gray-600 dark:text-gray-400">
                                    Actions
                                </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($category->items as $item)
                            <tr class="bg-white dark:bg-gray-900">
                                <td class="px-3 py-2 align-middle">
                                    @if (filled($item->image_path))
                                        <img
                                            src="{{ Storage::disk('public')->url($item->image_path) }}"
                                            alt=""
                                            class="h-10 w-10 rounded-md object-cover ring-1 ring-gray-950/10 dark:ring-white/10"
                                        />
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 align-middle font-medium text-gray-950 dark:text-white">
                                    {{ $item->name }}
                                </td>
                                <td class="max-w-xs px-3 py-2 align-middle text-gray-600 dark:text-gray-400">
                                    {{ $item->description ?: '—' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 align-middle text-gray-950 dark:text-white">
                                    @if ($item->price === null)
                                        —
                                    @else
                                        {{ number_format((float) $item->price, 2) }} {{ $item->currency }}
                                    @endif
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    @if ($item->is_available)
                                        <span class="text-xs font-medium text-success-600 dark:text-success-400">Yes</span>
                                    @else
                                        <span class="text-xs text-gray-500">No</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    @if ($item->is_featured)
                                        <span class="text-xs font-medium text-warning-600 dark:text-warning-400">Yes</span>
                                    @else
                                        <span class="text-xs text-gray-500">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 align-middle text-gray-600 dark:text-gray-400">
                                    {{ $item->display_order }}
                                </td>
                                @if ($this->canManage())
                                    <td class="px-3 py-2 text-end align-middle">
                                        <div class="inline-flex flex-wrap justify-end gap-2">
                                            <x-filament::button
                                                size="xs"
                                                color="gray"
                                                outlined
                                                type="button"
                                                wire:click="mountAction('editItem', @js(['item' => $item->id]))"
                                            >
                                                Edit
                                            </x-filament::button>
                                            <x-filament::button
                                                size="xs"
                                                color="danger"
                                                outlined
                                                type="button"
                                                wire:click="mountAction('deleteItem', @js(['item' => $item->id]))"
                                            >
                                                Delete
                                            </x-filament::button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="{{ $this->canManage() ? 8 : 7 }}"
                                    class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400"
                                >
                                    No items in this category yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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

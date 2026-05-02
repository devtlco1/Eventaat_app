@php
    use App\Models\RestaurantMenuItem;
    use Illuminate\Support\Facades\Storage;

    /** @var RestaurantMenuItem|null $record */
    $path = $record instanceof RestaurantMenuItem
        ? RestaurantMenuItem::normalizeStoredImagePath($record->image_path)
        : null;

    $hasFile = filled($path) && Storage::disk('public')->exists($path);
    $src = $hasFile ? asset('storage/'.ltrim((string) $path, '/')) : null;
@endphp

<div class="flex items-center justify-start py-0.5">
    @if ($src)
        <img
            src="{{ $src }}"
            alt=""
            loading="lazy"
            decoding="async"
            class="h-10 w-10 shrink-0 rounded-md object-cover ring-1 ring-gray-950/10 dark:ring-white/10"
        />
    @else
        <span class="fi-ta-placeholder flex h-10 w-10 items-center justify-center rounded-md bg-gray-50 text-sm text-gray-400 ring-1 ring-gray-950/5 dark:bg-white/5 dark:text-gray-500 dark:ring-white/10">
            —
        </span>
    @endif
</div>

@php
    use App\Models\RestaurantStoryItem;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    /** @var \App\Models\RestaurantStoryItem|null $record */
    $rawPath = $record?->media_path;
    if (is_array($rawPath)) {
        $rawPath = filled($rawPath) ? (string) reset($rawPath) : null;
    }
    $path = filled($rawPath) ? (string) $rawPath : null;

    $isImage = $record?->item_type === RestaurantStoryItem::TYPE_IMAGE;
    $isVideo = $record?->item_type === RestaurantStoryItem::TYPE_VIDEO;
    $isText = $record?->item_type === RestaurantStoryItem::TYPE_TEXT;

    $mediaUrl = $path ? asset('storage/' . ltrim($path, '/')) : null;
    $mediaMissing = $path && Storage::disk('public')->missing($path);
@endphp

<div class="flex items-center">
    @if (! $record)
        <span class="text-xs italic text-gray-500 dark:text-gray-400">—</span>
    @elseif ($isImage)
        @if (! $path)
            <span class="text-xs italic text-gray-500 dark:text-gray-400">No image</span>
        @elseif ($mediaMissing)
            <span class="text-xs italic text-danger-600 dark:text-danger-400">Missing file</span>
        @else
            <img
                src="{{ $mediaUrl }}"
                alt=""
                loading="lazy"
                class="rounded border border-gray-200 object-cover dark:border-gray-700 h-16 w-16"
            />
        @endif
    @elseif ($isVideo)
        @if (! $path)
            <span class="text-xs italic text-gray-500 dark:text-gray-400">No video</span>
        @elseif ($mediaMissing)
            <span class="text-xs italic text-danger-600 dark:text-danger-400">Missing file</span>
        @else
            <video
                muted
                preload="metadata"
                src="{{ $mediaUrl }}"
                class="rounded border border-gray-200 object-cover dark:border-gray-700 h-16 w-24"
            ></video>
        @endif
    @elseif ($isText)
        @if (filled($record->body))
            <span class="line-clamp-2 max-w-xs text-sm text-gray-950 dark:text-white">{{ Str::limit($record->body, 80) }}</span>
        @else
            <span class="text-xs italic text-gray-500 dark:text-gray-400">No text</span>
        @endif
    @else
        <span class="text-xs italic text-gray-500 dark:text-gray-400">—</span>
    @endif
</div>

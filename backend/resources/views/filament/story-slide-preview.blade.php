@php
    use App\Models\RestaurantStoryItem;
    use Illuminate\Support\Facades\Storage;

    /** @var \App\Models\RestaurantStoryItem|null $record */
    $record = $record ?? null;

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

<div class="fi-in-text">
    @if (! $record)
        <p class="text-sm italic text-gray-500 dark:text-gray-400">No slide</p>
    @elseif ($isImage)
        @if (! $path)
            <p class="text-sm italic text-gray-500 dark:text-gray-400">No image uploaded</p>
        @elseif ($mediaMissing)
            <p class="text-sm italic text-danger-600 dark:text-danger-400">Media file not found</p>
        @else
            <img
                src="{{ $mediaUrl }}"
                alt=""
                loading="lazy"
                class="rounded-md border border-gray-200 object-contain dark:border-gray-700 max-h-72 w-auto"
            />
        @endif
    @elseif ($isVideo)
        @if (! $path)
            <p class="text-sm italic text-gray-500 dark:text-gray-400">No video uploaded</p>
        @elseif ($mediaMissing)
            <p class="text-sm italic text-danger-600 dark:text-danger-400">Media file not found</p>
        @else
            <video
                controls
                preload="metadata"
                src="{{ $mediaUrl }}"
                class="rounded-md border border-gray-200 dark:border-gray-700 max-h-72 w-full"
            ></video>
        @endif
    @elseif ($isText)
        @if (filled($record->body))
            <div class="whitespace-pre-wrap text-base leading-relaxed text-gray-950 dark:text-white">{{ $record->body }}</div>
        @else
            <p class="text-sm italic text-gray-500 dark:text-gray-400">No text</p>
        @endif
    @else
        <p class="text-sm italic text-gray-500 dark:text-gray-400">Unknown slide type</p>
    @endif
</div>

<?php

namespace App\Filament\Support;

use App\Models\RestaurantStoryItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

final class StoryItemSlidePreview
{
    public static function toHtml(RestaurantStoryItem $record, bool $compact = false): HtmlString
    {
        $maxMedia = $compact ? 'max-h-40' : 'max-h-72';

        return new HtmlString(match ($record->item_type) {
            RestaurantStoryItem::TYPE_IMAGE => self::imageHtml($record->media_path, $maxMedia),
            RestaurantStoryItem::TYPE_VIDEO => self::videoHtml($record->media_path, $maxMedia),
            RestaurantStoryItem::TYPE_TEXT => self::textHtml($record->body, $compact),
            default => '<p class="text-sm text-gray-500 dark:text-gray-400">Unknown slide type</p>',
        });
    }

    private static function imageHtml(mixed $path, string $maxClass): string
    {
        $path = self::normalizePath($path);
        if (! filled($path)) {
            return '<p class="text-sm text-gray-500 dark:text-gray-400 italic">No image</p>';
        }

        $url = e(Storage::disk('public')->url($path));

        return '<img src="'.$url.'" alt="" class="rounded-md object-contain border border-gray-200 dark:border-gray-600 '.$maxClass.' w-auto" loading="lazy" />';
    }

    private static function videoHtml(mixed $path, string $maxClass): string
    {
        $path = self::normalizePath($path);
        if (! filled($path)) {
            return '<p class="text-sm text-gray-500 dark:text-gray-400 italic">No video</p>';
        }

        $url = e(Storage::disk('public')->url($path));

        return '<video controls class="rounded-md border border-gray-200 dark:border-gray-600 '.$maxClass.' w-full" preload="metadata" src="'.$url.'"></video>';
    }

    private static function textHtml(?string $body, bool $compact): string
    {
        if (! filled($body)) {
            return '<p class="text-sm text-gray-500 dark:text-gray-400 italic">No text</p>';
        }

        $wrap = $compact ? 'text-sm leading-snug' : 'text-base leading-relaxed';

        return '<div class="whitespace-pre-wrap text-gray-950 dark:text-white '.$wrap.'">'.e($body).'</div>';
    }

    private static function normalizePath(mixed $path): ?string
    {
        if (is_array($path)) {
            return filled($path) ? (string) reset($path) : null;
        }

        return filled($path) ? (string) $path : null;
    }
}

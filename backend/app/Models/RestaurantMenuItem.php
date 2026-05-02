<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RestaurantMenuItem extends Model
{
    protected $fillable = [
        'restaurant_menu_category_id',
        'name',
        'description',
        'image_path',
        'price',
        'currency',
        'is_available',
        'is_featured',
        'display_order',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_menu_category_id' => 'integer',
            'price' => 'decimal:2',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RestaurantMenuCategory::class, 'restaurant_menu_category_id');
    }

    /**
     * Normalize menu item image paths for storage and Filament previews.
     * Expects the final DB value to be a path relative to the public disk root (e.g. menus/items/foo.png).
     */
    public static function normalizeStoredImagePath(mixed $path): ?string
    {
        if (is_array($path)) {
            $strings = array_values(array_filter(
                $path,
                fn ($p): bool => is_string($p) && filled(trim($p)),
            ));

            return self::normalizeStoredImagePath($strings[0] ?? null);
        }

        if (! is_string($path)) {
            return null;
        }

        $path = trim($path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, '[')) {
            $decoded = json_decode($path, true);
            if (is_array($decoded)) {
                return self::normalizeStoredImagePath($decoded);
            }
        }

        $publicUrl = rtrim((string) config('filesystems.disks.public.url'), '/');
        if ($publicUrl !== '' && str_starts_with($path, $publicUrl.'/')) {
            $path = substr($path, strlen($publicUrl) + 1);
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && str_starts_with($path, $appUrl.'/')) {
            $path = substr($path, strlen($appUrl) + 1);
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return $path !== '' ? $path : null;
    }

    protected static function booted(): void
    {
        static::saving(function (RestaurantMenuItem $item): void {
            if (blank($item->currency)) {
                $item->currency = 'IQD';
            }

            $originalNormalized = $item->exists
                ? self::normalizeStoredImagePath($item->getOriginal('image_path'))
                : null;

            $incomingNormalized = self::normalizeStoredImagePath($item->image_path);

            if (
                $item->exists
                && filled($originalNormalized)
                && $originalNormalized !== $incomingNormalized
                && Storage::disk('public')->exists($originalNormalized)
            ) {
                Storage::disk('public')->delete($originalNormalized);
            }

            $item->image_path = $incomingNormalized;

            $validator = Validator::make($item->getAttributes(), [
                'restaurant_menu_category_id' => ['required', 'integer', Rule::exists('restaurant_menu_categories', 'id')],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'image_path' => ['nullable', 'string', 'max:2048'],
                'price' => ['nullable', 'numeric', 'min:0'],
                'currency' => ['required', 'string', 'max:8'],
                'is_available' => ['boolean'],
                'is_featured' => ['boolean'],
                'display_order' => ['nullable', 'integer', 'min:0'],
                'notes' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            /** @var RestaurantMenuCategory|null $category */
            $category = RestaurantMenuCategory::query()->find($item->restaurant_menu_category_id);

            if ($category) {
                $menu = $category->relationLoaded('menu')
                    ? $category->menu
                    : $category->menu()->first();

                if (! $menu || ! $menu->isStructured()) {
                    throw ValidationException::withMessages([
                        'restaurant_menu_category_id' => ['Items can only be added when the menu mode is structured.'],
                    ]);
                }
            }
        });
    }
}

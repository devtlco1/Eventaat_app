<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RestaurantMenuItem extends Model
{
    protected $fillable = [
        'restaurant_menu_category_id',
        'name',
        'description',
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

    protected static function booted(): void
    {
        static::saving(function (RestaurantMenuItem $item): void {
            if (blank($item->currency)) {
                $item->currency = 'IQD';
            }

            $validator = Validator::make($item->getAttributes(), [
                'restaurant_menu_category_id' => ['required', 'integer', Rule::exists('restaurant_menu_categories', 'id')],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RestaurantMenuCategory extends Model
{
    protected $fillable = [
        'restaurant_menu_id',
        'name',
        'description',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_menu_id' => 'integer',
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(RestaurantMenu::class, 'restaurant_menu_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RestaurantMenuItem::class, 'restaurant_menu_category_id')->orderBy('display_order');
    }

    protected static function booted(): void
    {
        static::saving(function (RestaurantMenuCategory $category): void {
            $validator = Validator::make($category->getAttributes(), [
                'restaurant_menu_id' => ['required', 'integer', Rule::exists('restaurant_menus', 'id')],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'display_order' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['boolean'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $menu = RestaurantMenu::query()->find($category->restaurant_menu_id);

            if ($menu && ! $menu->isStructured()) {
                throw ValidationException::withMessages([
                    'restaurant_menu_id' => ['Categories can only be added when the menu mode is structured.'],
                ]);
            }
        });
    }
}

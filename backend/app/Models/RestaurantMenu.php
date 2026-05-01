<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RestaurantMenu extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    public const MODE_STRUCTURED = 'structured';

    public const MODE_PDF_UPLOAD = 'pdf_upload';

    public const MODE_EXTERNAL_LINK = 'external_link';

    /** @var list<string> */
    public const MENU_MODES = [
        self::MODE_STRUCTURED,
        self::MODE_PDF_UPLOAD,
        self::MODE_EXTERNAL_LINK,
    ];

    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'title',
        'slug',
        'status',
        'menu_mode',
        'menu_file_path',
        'menu_url',
        'description',
        'notes',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'branch_id' => 'integer',
            'display_order' => 'integer',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(RestaurantMenuCategory::class)->orderBy('display_order');
    }

    public function menuItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            RestaurantMenuItem::class,
            RestaurantMenuCategory::class,
            'restaurant_menu_id',
            'restaurant_menu_category_id',
            'id',
            'id',
        )->orderBy('restaurant_menu_items.display_order');
    }

    protected static function booted(): void
    {
        static::saving(function (RestaurantMenu $menu): void {
            if (blank($menu->status)) {
                $menu->status = self::STATUS_DRAFT;
            }

            if (blank($menu->menu_mode)) {
                $menu->menu_mode = self::MODE_STRUCTURED;
            }

            if (blank($menu->slug) && filled($menu->title)) {
                $menu->slug = str($menu->title)->slug()->toString();
            }

            $originalPath = $menu->exists ? $menu->getOriginal('menu_file_path') : null;

            if ($menu->menu_mode !== self::MODE_PDF_UPLOAD) {
                if ($originalPath && Storage::disk('public')->exists($originalPath)) {
                    Storage::disk('public')->delete($originalPath);
                }
                $menu->menu_file_path = null;
            } elseif ($menu->isDirty('menu_file_path') && $originalPath && $originalPath !== $menu->menu_file_path && Storage::disk('public')->exists($originalPath)) {
                Storage::disk('public')->delete($originalPath);
            }

            if ($menu->menu_mode !== self::MODE_EXTERNAL_LINK) {
                $menu->menu_url = null;
            }

            if (filled($menu->branch_id)) {
                $branchRestaurantId = Branch::query()->whereKey($menu->branch_id)->value('restaurant_id');
                if ($branchRestaurantId === null) {
                    throw ValidationException::withMessages([
                        'branch_id' => ['The selected branch could not be found.'],
                    ]);
                }

                if ((int) $branchRestaurantId !== (int) $menu->restaurant_id) {
                    throw ValidationException::withMessages([
                        'branch_id' => ['The selected branch does not belong to the selected restaurant.'],
                    ]);
                }
            }

            $validator = Validator::make($menu->getAttributes(), [
                'restaurant_id' => ['required', 'integer', Rule::exists('restaurants', 'id')],
                'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
                'title' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('restaurant_menus', 'slug')->ignore($menu->id)],
                'status' => ['required', 'string', Rule::in(self::STATUSES)],
                'menu_mode' => ['required', 'string', Rule::in(self::MENU_MODES)],
                'menu_file_path' => ['nullable', 'string', 'max:2048'],
                'menu_url' => ['nullable', 'string', 'max:2048'],
                'description' => ['nullable', 'string'],
                'notes' => ['nullable', 'string'],
                'display_order' => ['nullable', 'integer', 'min:0'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            if ($menu->menu_mode === self::MODE_PDF_UPLOAD && blank($menu->menu_file_path)) {
                throw ValidationException::withMessages([
                    'menu_file_path' => ['Upload a PDF for this menu mode.'],
                ]);
            }

            if ($menu->menu_mode === self::MODE_EXTERNAL_LINK) {
                if (blank($menu->menu_url)) {
                    throw ValidationException::withMessages([
                        'menu_url' => ['A menu URL is required for external link mode.'],
                    ]);
                }

                $urlValidator = Validator::make(
                    ['menu_url' => $menu->menu_url],
                    ['menu_url' => ['required', 'url', 'max:2048']]
                );

                if ($urlValidator->fails()) {
                    throw new ValidationException($urlValidator);
                }
            }
        });
    }

    public function isStructured(): bool
    {
        return $this->menu_mode === self::MODE_STRUCTURED;
    }
}

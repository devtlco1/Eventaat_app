<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class RestaurantStory extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_REVIEW,
        self::STATUS_PUBLISHED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELLED,
    ];

    public const TYPE_IMAGE = 'image';

    public const TYPE_VIDEO = 'video';

    public const TYPE_TEXT = 'text';

    /** @var list<string> */
    public const STORY_TYPES = [
        self::TYPE_IMAGE,
        self::TYPE_VIDEO,
        self::TYPE_TEXT,
    ];

    public const LIFETIME_12H = '12h';

    public const LIFETIME_24H = '24h';

    public const LIFETIME_48H = '48h';

    public const LIFETIME_MANUAL = 'manual';

    /** @var list<string> */
    public const LIFETIME_MODES = [
        self::LIFETIME_12H,
        self::LIFETIME_24H,
        self::LIFETIME_48H,
        self::LIFETIME_MANUAL,
    ];

    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'title',
        'slug',
        'story_type',
        'media_url',
        'body',
        'cta_label',
        'cta_url',
        'status',
        'starts_at',
        'ends_at',
        'lifetime_mode',
        'lifetime_hours',
        'display_order',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'display_order' => 'int',
            'lifetime_hours' => 'int',
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

    public function items(): HasMany
    {
        return $this->hasMany(RestaurantStoryItem::class, 'restaurant_story_id');
    }

    public static function lifetimeHoursFromMode(?string $mode): ?int
    {
        return match ($mode) {
            self::LIFETIME_12H => 12,
            self::LIFETIME_24H => 24,
            self::LIFETIME_48H => 48,
            default => null,
        };
    }

    /**
     * When publishing / approving: set starts_at if missing and derive ends_at from lifetime_mode unless manual or ends_at already set.
     */
    public function applyLifetimeWindowForPublishing(?Carbon $now = null): void
    {
        $now ??= Carbon::now();

        if ($this->ends_at !== null) {
            return;
        }

        if ($this->lifetime_mode === self::LIFETIME_MANUAL) {
            return;
        }

        if ($this->starts_at === null) {
            $this->starts_at = $now->clone();
        }

        $hours = self::lifetimeHoursFromMode($this->lifetime_mode);
        if ($hours === null) {
            return;
        }

        $this->ends_at = $this->starts_at->clone()->addHours($hours);
    }

    public function hasRenderableLegacyContent(): bool
    {
        if ($this->story_type === self::TYPE_TEXT) {
            return filled($this->body);
        }

        return filled($this->media_url);
    }

    public function hasRenderableItems(): bool
    {
        return $this->items()->exists();
    }

    public function hasRenderableContent(): bool
    {
        return $this->hasRenderableItems() || $this->hasRenderableLegacyContent();
    }
}

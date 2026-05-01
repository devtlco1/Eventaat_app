<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantOffer extends Model
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

    public const TYPE_TEXT_ONLY = 'text_only';
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED_AMOUNT = 'fixed_amount';

    /** @var list<string> */
    public const OFFER_TYPES = [
        self::TYPE_TEXT_ONLY,
        self::TYPE_PERCENTAGE,
        self::TYPE_FIXED_AMOUNT,
    ];

    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'title',
        'slug',
        'description',
        'status',
        'offer_type',
        'discount_value',
        'starts_at',
        'ends_at',
        'terms',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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
}


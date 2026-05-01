<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RestaurantStoryItem extends Model
{
    public const TYPE_IMAGE = 'image';

    public const TYPE_VIDEO = 'video';

    public const TYPE_TEXT = 'text';

    /** @var list<string> */
    public const ITEM_TYPES = [
        self::TYPE_IMAGE,
        self::TYPE_VIDEO,
        self::TYPE_TEXT,
    ];

    protected $fillable = [
        'restaurant_story_id',
        'item_type',
        'media_path',
        'body',
        'cta_label',
        'cta_url',
        'sort_order',
        'item_duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'item_duration_seconds' => 'integer',
        ];
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(RestaurantStory::class, 'restaurant_story_id');
    }

    protected static function booted(): void
    {
        static::saving(function (RestaurantStoryItem $item): void {
            $validator = Validator::make($item->getAttributes(), [
                'item_type' => ['required', 'string', 'in:'.implode(',', self::ITEM_TYPES)],
                'media_path' => ['nullable', 'string', 'max:2048'],
                'body' => ['nullable', 'string'],
                'sort_order' => ['nullable', 'integer'],
                'item_duration_seconds' => ['nullable', 'integer', 'min:1'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $mediaFilled = filled($item->media_path);
            $bodyFilled = filled($item->body);

            if (in_array($item->item_type, [self::TYPE_IMAGE, self::TYPE_VIDEO], true) && ! $mediaFilled) {
                throw ValidationException::withMessages([
                    'media_path' => ['Media is required for image/video items.'],
                ]);
            }

            if ($item->item_type === self::TYPE_TEXT && ! $bodyFilled) {
                throw ValidationException::withMessages([
                    'body' => ['Body is required for text items.'],
                ]);
            }

            if (in_array($item->item_type, [self::TYPE_IMAGE, self::TYPE_VIDEO], true) && $bodyFilled) {
                // Avoid ambiguous payloads for media slides.
                $item->body = null;
            }

            if ($item->item_type === self::TYPE_TEXT && $mediaFilled) {
                $item->media_path = null;
            }
        });
    }
}

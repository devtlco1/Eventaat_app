<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RestaurantReview extends Model
{
    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_HIDDEN = 'hidden';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING_REVIEW,
        self::STATUS_PUBLISHED,
        self::STATUS_REJECTED,
        self::STATUS_HIDDEN,
    ];

    public const SOURCE_DASHBOARD = 'dashboard';

    public const SOURCE_MOBILE = 'mobile';

    public const SOURCE_IMPORT = 'import';

    /** @var list<string> */
    public const SOURCES = [
        self::SOURCE_DASHBOARD,
        self::SOURCE_MOBILE,
        self::SOURCE_IMPORT,
    ];

    public const RATING_MIN = 1;

    public const RATING_MAX = 5;

    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'booking_id',
        'user_id',
        'customer_name',
        'customer_phone',
        'rating',
        'comment',
        'status',
        'source',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'restaurant_id' => 'integer',
            'branch_id' => 'integer',
            'booking_id' => 'integer',
            'user_id' => 'integer',
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

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::saving(function (RestaurantReview $review): void {
            if (blank($review->status)) {
                $review->status = self::STATUS_PENDING_REVIEW;
            }

            if (blank($review->source)) {
                $review->source = self::SOURCE_DASHBOARD;
            }

            $validator = Validator::make($review->getAttributes(), [
                'restaurant_id' => ['required', 'integer', Rule::exists('restaurants', 'id')],
                'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
                'booking_id' => ['nullable', 'integer', Rule::exists('bookings', 'id')],
                'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
                'customer_name' => ['nullable', 'string', 'max:255'],
                'customer_phone' => ['nullable', 'string', 'max:32'],
                'rating' => ['required', 'integer', 'min:'.self::RATING_MIN, 'max:'.self::RATING_MAX],
                'comment' => ['nullable', 'string'],
                'status' => ['required', 'string', Rule::in(self::STATUSES)],
                'source' => ['required', 'string', Rule::in(self::SOURCES)],
                'admin_notes' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            if (filled($review->branch_id)) {
                $branch = Branch::query()
                    ->whereKey($review->branch_id)
                    ->where('restaurant_id', $review->restaurant_id)
                    ->exists();

                if (! $branch) {
                    throw ValidationException::withMessages([
                        'branch_id' => ['The selected branch does not belong to the selected restaurant.'],
                    ]);
                }
            }

            if (filled($review->booking_id)) {
                /** @var Booking|null $booking */
                $booking = Booking::query()->find($review->booking_id);

                if (! $booking) {
                    throw ValidationException::withMessages([
                        'booking_id' => ['The selected booking could not be found.'],
                    ]);
                }

                if ((int) $booking->restaurant_id !== (int) $review->restaurant_id) {
                    throw ValidationException::withMessages([
                        'booking_id' => ['The selected booking does not belong to the selected restaurant.'],
                    ]);
                }

                if (filled($review->branch_id) && (int) $booking->branch_id !== (int) $review->branch_id) {
                    throw ValidationException::withMessages([
                        'booking_id' => ['The selected booking does not belong to the selected branch.'],
                    ]);
                }

                if (blank($review->user_id) && filled($booking->customer_id)) {
                    $review->user_id = (int) $booking->customer_id;
                }
            }
        });
    }
}

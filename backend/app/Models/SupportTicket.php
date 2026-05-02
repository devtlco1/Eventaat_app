<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupportTicket extends Model
{
    public const CATEGORY_GENERAL = 'general';

    public const CATEGORY_BOOKING = 'booking';

    public const CATEGORY_RESTAURANT = 'restaurant';

    public const CATEGORY_PAYMENT = 'payment';

    public const CATEGORY_APP = 'app';

    public const CATEGORY_OTHER = 'other';

    /** @var list<string> */
    public const CATEGORIES = [
        self::CATEGORY_GENERAL,
        self::CATEGORY_BOOKING,
        self::CATEGORY_RESTAURANT,
        self::CATEGORY_PAYMENT,
        self::CATEGORY_APP,
        self::CATEGORY_OTHER,
    ];

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    /** @var list<string> */
    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_NORMAL,
        self::PRIORITY_HIGH,
        self::PRIORITY_URGENT,
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
    ];

    public const SOURCE_DASHBOARD = 'dashboard';

    public const SOURCE_MOBILE = 'mobile';

    public const SOURCE_PHONE = 'phone';

    public const SOURCE_WHATSAPP = 'whatsapp';

    public const SOURCE_IMPORT = 'import';

    /** @var list<string> */
    public const SOURCES = [
        self::SOURCE_DASHBOARD,
        self::SOURCE_MOBILE,
        self::SOURCE_PHONE,
        self::SOURCE_WHATSAPP,
        self::SOURCE_IMPORT,
    ];

    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'booking_id',
        'user_id',
        'customer_name',
        'customer_phone',
        'subject',
        'category',
        'priority',
        'status',
        'source',
        'message',
        'internal_notes',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'branch_id' => 'integer',
            'booking_id' => 'integer',
            'user_id' => 'integer',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
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

    public function activities(): HasMany
    {
        return $this->hasMany(SupportTicketActivity::class)->orderByDesc('created_at');
    }

    public function callCenterCalls(): HasMany
    {
        return $this->hasMany(CallCenterCall::class)->orderByDesc('created_at');
    }

    protected static function booted(): void
    {
        static::saving(function (SupportTicket $ticket): void {
            if (blank($ticket->category)) {
                $ticket->category = self::CATEGORY_GENERAL;
            }

            if (blank($ticket->priority)) {
                $ticket->priority = self::PRIORITY_NORMAL;
            }

            if (blank($ticket->status)) {
                $ticket->status = self::STATUS_OPEN;
            }

            if (blank($ticket->source)) {
                $ticket->source = self::SOURCE_DASHBOARD;
            }

            if (blank($ticket->restaurant_id)) {
                if (filled($ticket->branch_id) || filled($ticket->booking_id)) {
                    throw ValidationException::withMessages([
                        'restaurant_id' => ['A restaurant is required when a branch or booking is selected.'],
                    ]);
                }
            }

            if (filled($ticket->branch_id)) {
                $branchRestaurantId = Branch::query()->whereKey($ticket->branch_id)->value('restaurant_id');
                if ($branchRestaurantId === null) {
                    throw ValidationException::withMessages([
                        'branch_id' => ['The selected branch could not be found.'],
                    ]);
                }

                if (filled($ticket->restaurant_id) && (int) $branchRestaurantId !== (int) $ticket->restaurant_id) {
                    throw ValidationException::withMessages([
                        'branch_id' => ['The selected branch does not belong to the selected restaurant.'],
                    ]);
                }

                if (blank($ticket->restaurant_id)) {
                    throw ValidationException::withMessages([
                        'branch_id' => ['Select a restaurant before choosing a branch.'],
                    ]);
                }
            }

            if (filled($ticket->booking_id)) {
                /** @var Booking|null $booking */
                $booking = Booking::query()->find($ticket->booking_id);

                if (! $booking) {
                    throw ValidationException::withMessages([
                        'booking_id' => ['The selected booking could not be found.'],
                    ]);
                }

                if (blank($ticket->restaurant_id)) {
                    throw ValidationException::withMessages([
                        'booking_id' => ['A restaurant is required when a booking is selected.'],
                    ]);
                }

                if ((int) $booking->restaurant_id !== (int) $ticket->restaurant_id) {
                    throw ValidationException::withMessages([
                        'booking_id' => ['The selected booking does not belong to the selected restaurant.'],
                    ]);
                }

                if (filled($ticket->branch_id) && (int) $booking->branch_id !== (int) $ticket->branch_id) {
                    throw ValidationException::withMessages([
                        'booking_id' => ['The selected booking does not belong to the selected branch.'],
                    ]);
                }

                if (blank($ticket->user_id) && filled($booking->customer_id)) {
                    $ticket->user_id = (int) $booking->customer_id;
                }
            }

            if (filled($ticket->user_id)) {
                /** @var User|null $user */
                $user = User::query()->find($ticket->user_id);
                if ($user) {
                    if (blank($ticket->customer_name) && filled($user->name)) {
                        $ticket->customer_name = (string) $user->name;
                    }
                    if (blank($ticket->customer_phone) && filled($user->phone)) {
                        $ticket->customer_phone = (string) $user->phone;
                    }
                }
            }

            $validator = Validator::make($ticket->getAttributes(), [
                'restaurant_id' => ['nullable', 'integer', Rule::exists('restaurants', 'id')],
                'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
                'booking_id' => ['nullable', 'integer', Rule::exists('bookings', 'id')],
                'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
                'customer_name' => ['nullable', 'string', 'max:255'],
                'customer_phone' => ['nullable', 'string', 'max:32'],
                'subject' => ['required', 'string', 'max:255'],
                'category' => ['required', 'string', Rule::in(self::CATEGORIES)],
                'priority' => ['required', 'string', Rule::in(self::PRIORITIES)],
                'status' => ['required', 'string', Rule::in(self::STATUSES)],
                'source' => ['required', 'string', Rule::in(self::SOURCES)],
                'message' => ['nullable', 'string'],
                'internal_notes' => ['nullable', 'string'],
                'resolved_at' => ['nullable', 'date'],
                'closed_at' => ['nullable', 'date'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            if ($ticket->status === self::STATUS_RESOLVED && $ticket->resolved_at === null) {
                $ticket->resolved_at = now();
            }

            if ($ticket->status === self::STATUS_CLOSED && $ticket->closed_at === null) {
                $ticket->closed_at = now();
            }
        });
    }
}

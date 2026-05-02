<?php

namespace App\Models;

use App\Enums\CallCenterCallDirection;
use App\Enums\CallCenterCallOutcome;
use App\Enums\CallCenterCallReason;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CallCenterCall extends Model
{
    protected $fillable = [
        'restaurant_id',
        'booking_id',
        'support_ticket_id',
        'customer_user_id',
        'handled_by_user_id',
        'direction',
        'reason',
        'outcome',
        'phone',
        'caller_name',
        'notes',
        'follow_up_at',
        'completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'booking_id' => 'integer',
            'support_ticket_id' => 'integer',
            'customer_user_id' => 'integer',
            'handled_by_user_id' => 'integer',
            'direction' => CallCenterCallDirection::class,
            'reason' => CallCenterCallReason::class,
            'outcome' => CallCenterCallOutcome::class,
            'follow_up_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function supportTicket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class);
    }

    public function customerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function handledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id');
    }

    protected static function booted(): void
    {
        static::saving(function (CallCenterCall $call): void {
            if ($call->completed_at && $call->outcome === CallCenterCallOutcome::Pending) {
                $call->outcome = CallCenterCallOutcome::Resolved;
            }

            if (filled($call->booking_id)) {
                /** @var Booking|null $booking */
                $booking = Booking::query()->find($call->booking_id);

                if (! $booking) {
                    throw ValidationException::withMessages([
                        'booking_id' => ['The selected booking could not be found.'],
                    ]);
                }

                if (blank($call->restaurant_id)) {
                    $call->restaurant_id = (int) $booking->restaurant_id;
                } elseif ((int) $booking->restaurant_id !== (int) $call->restaurant_id) {
                    throw ValidationException::withMessages([
                        'booking_id' => ['The selected booking does not belong to the selected restaurant.'],
                    ]);
                }
            }

            if (filled($call->support_ticket_id)) {
                /** @var SupportTicket|null $ticket */
                $ticket = SupportTicket::query()->find($call->support_ticket_id);

                if (! $ticket) {
                    throw ValidationException::withMessages([
                        'support_ticket_id' => ['The selected support ticket could not be found.'],
                    ]);
                }

                if (
                    filled($call->restaurant_id)
                    && filled($ticket->restaurant_id)
                    && (int) $ticket->restaurant_id !== (int) $call->restaurant_id
                ) {
                    throw ValidationException::withMessages([
                        'support_ticket_id' => ['The selected ticket does not match the selected restaurant.'],
                    ]);
                }
            }

            $direction = $call->direction instanceof BackedEnum ? $call->direction->value : $call->direction;
            $reason = $call->reason instanceof BackedEnum ? $call->reason->value : $call->reason;
            $outcome = $call->outcome instanceof BackedEnum ? $call->outcome->value : $call->outcome;

            $validator = Validator::make([
                'restaurant_id' => $call->restaurant_id,
                'booking_id' => $call->booking_id,
                'support_ticket_id' => $call->support_ticket_id,
                'customer_user_id' => $call->customer_user_id,
                'handled_by_user_id' => $call->handled_by_user_id,
                'direction' => $direction,
                'reason' => $reason,
                'outcome' => $outcome,
                'phone' => $call->phone,
                'caller_name' => $call->caller_name,
                'notes' => $call->notes,
                'follow_up_at' => $call->follow_up_at,
                'completed_at' => $call->completed_at,
                'metadata' => $call->metadata,
            ], [
                'restaurant_id' => ['nullable', 'integer', Rule::exists('restaurants', 'id')],
                'booking_id' => ['nullable', 'integer', Rule::exists('bookings', 'id')],
                'support_ticket_id' => ['nullable', 'integer', Rule::exists('support_tickets', 'id')],
                'customer_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
                'handled_by_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
                'direction' => ['required', 'string', Rule::enum(CallCenterCallDirection::class)],
                'reason' => ['required', 'string', Rule::enum(CallCenterCallReason::class)],
                'outcome' => ['required', 'string', Rule::enum(CallCenterCallOutcome::class)],
                'phone' => ['nullable', 'string', 'max:191'],
                'caller_name' => ['nullable', 'string', 'max:255'],
                'notes' => ['nullable', 'string'],
                'follow_up_at' => ['nullable', 'date'],
                'completed_at' => ['nullable', 'date'],
                'metadata' => ['nullable', 'array'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        });
    }
}

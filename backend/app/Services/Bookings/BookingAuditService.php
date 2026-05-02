<?php

namespace App\Services\Bookings;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Throwable;

class BookingAuditService
{
    /**
     * Record one immutable audit row after a successful booking status transition.
     * Failures are reported only — transitions must never depend on auditing.
     */
    public function recordTransition(Booking $booking, string $action, ?BookingStatus $fromStatus, BookingStatus $toStatus): void
    {
        try {
            $actor = $this->resolveActor();

            BookingAuditLog::create([
                'booking_id' => $booking->id,
                'actor_id' => $actor?->id,
                'actor_type' => $actor !== null ? User::class : null,
                'action' => $action,
                'from_status' => $fromStatus?->value,
                'to_status' => $toStatus->value,
                'message' => null,
                'metadata' => null,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function resolveActor(): ?User
    {
        foreach (['sanctum', 'web'] as $guardName) {
            $guard = Auth::guard($guardName);
            if ($guard->check()) {
                $user = $guard->user();

                return $user instanceof User ? $user : null;
            }
        }

        return null;
    }
}

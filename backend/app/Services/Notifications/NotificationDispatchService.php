<?php

namespace App\Services\Notifications;

use App\Models\BookingNotification;
use Illuminate\Support\Carbon;
use Throwable;

class NotificationDispatchService
{
    public const CHANNEL_INTERNAL = 'internal';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_FAILED = 'failed';

    public function markSent(BookingNotification $notification): bool
    {
        return $this->transition($notification, self::STATUS_SENT);
    }

    public function markSkipped(BookingNotification $notification): bool
    {
        return $this->transition($notification, self::STATUS_SKIPPED);
    }

    public function markFailed(BookingNotification $notification, string $reason): bool
    {
        $reason = trim($reason);
        if ($reason === '') {
            return false;
        }

        return $this->transition($notification, self::STATUS_FAILED, $reason);
    }

    private function transition(BookingNotification $notification, string $toStatus, ?string $failureReason = null): bool
    {
        if ($notification->channel !== self::CHANNEL_INTERNAL) {
            return false;
        }

        if ($notification->status !== self::STATUS_PENDING) {
            return false;
        }

        $now = Carbon::now();

        try {
            return match ($toStatus) {
                self::STATUS_SENT => (bool) $notification->forceFill([
                    'status' => self::STATUS_SENT,
                    'sent_at' => $now,
                    'failed_at' => null,
                    'failure_reason' => null,
                ])->save(),
                self::STATUS_SKIPPED => (bool) $notification->forceFill([
                    'status' => self::STATUS_SKIPPED,
                    'sent_at' => null,
                    'failed_at' => null,
                    'failure_reason' => null,
                ])->save(),
                self::STATUS_FAILED => (bool) $notification->forceFill([
                    'status' => self::STATUS_FAILED,
                    'sent_at' => null,
                    'failed_at' => $now,
                    'failure_reason' => $failureReason,
                ])->save(),
                default => false,
            };
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}


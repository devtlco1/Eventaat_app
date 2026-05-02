<?php

namespace App\Services\Notifications;

use App\Models\Booking;
use App\Models\BookingNotification;
use App\Models\NotificationTemplate;
use App\Models\User;
use Throwable;

class BookingNotificationService
{
    /**
     * Record an internal outbox row for a booking lifecycle event.
     * Failures are reported only — booking flows must never depend on this succeeding.
     */
    public function record(Booking $booking, string $event): void
    {
        if (! in_array($event, BookingNotification::EVENTS, true)) {
            return;
        }

        try {
            $booking->loadMissing(['customer', 'restaurant', 'branch']);

            /** @var User|null $customer */
            $customer = $booking->customer;
            $recipientPhone = $customer?->phone ? (string) $customer->phone : null;
            $recipientName = $customer && trim((string) $customer->name) !== ''
                ? trim((string) $customer->name)
                : null;

            [$title, $message] = $this->copyForEventWithTemplateFallback($booking, $event);

            BookingNotification::create([
                'booking_id' => $booking->id,
                'user_id' => $booking->customer_id,
                'channel' => 'internal',
                'event' => $event,
                'recipient_phone' => $recipientPhone,
                'recipient_name' => $recipientName,
                'title' => $title,
                'message' => $message,
                'status' => 'pending',
                'payload' => $this->payloadSnapshot($booking, $event, $title, $message),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function copyForEventWithTemplateFallback(Booking $booking, string $event): array
    {
        try {
            $template = NotificationTemplate::query()
                ->where('event', $event)
                ->where('channel', 'internal')
                ->where('locale', 'en')
                ->where('is_active', true)
                ->first();

            if (! $template) {
                return $this->copyForEvent($booking, $event);
            }

            $data = $this->templateData($booking);

            $renderer = app(NotificationTemplateRenderer::class);

            return [
                $renderer->render((string) $template->title_template, $data),
                $renderer->render((string) $template->body_template, $data),
            ];
        } catch (Throwable $e) {
            report($e);

            return $this->copyForEvent($booking, $event);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function templateData(Booking $booking): array
    {
        $customerName = $booking->customer && trim((string) $booking->customer->name) !== ''
            ? trim((string) $booking->customer->name)
            : null;

        $customerPhone = $booking->customer?->phone ? (string) $booking->customer->phone : null;

        return array_merge([
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'restaurant_name' => $booking->restaurant?->name,
            'branch_name' => $booking->branch?->name,
            'booking_id' => $booking->id,
            'booking_status' => $booking->status?->value,
            'starts_at' => $booking->starts_at?->toIso8601String(),
            'party_size' => $booking->party_size,
        ], $this->bookingSchedulePlaceholders($booking));
    }

    /**
     * @return array{booking_date: ?string, booking_time: ?string}
     */
    private function bookingSchedulePlaceholders(Booking $booking): array
    {
        $starts = $booking->starts_at;
        if (! $starts) {
            return ['booking_date' => null, 'booking_time' => null];
        }

        $tz = (string) config('app.timezone', 'UTC');
        $local = $starts->copy()->timezone($tz);

        return [
            'booking_date' => $local->format('Y-m-d'),
            'booking_time' => $local->format('H:i'),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function copyForEvent(Booking $booking, string $event): array
    {
        $restaurant = $booking->restaurant?->name ?? 'Restaurant';
        $when = $booking->starts_at?->toIso8601String() ?? '';

        return match ($event) {
            BookingNotification::EVENT_BOOKING_REQUESTED => [
                'Booking requested',
                "A new booking was created at {$restaurant}. Starts at {$when}. Booking #{$booking->id}.",
            ],
            BookingNotification::EVENT_BOOKING_ACCEPTED => [
                'Booking accepted',
                "Booking #{$booking->id} at {$restaurant} was accepted. Starts at {$when}.",
            ],
            BookingNotification::EVENT_BOOKING_REJECTED => [
                'Booking rejected',
                "Booking #{$booking->id} at {$restaurant} was rejected.",
            ],
            BookingNotification::EVENT_BOOKING_CANCELLED => [
                'Booking cancelled',
                "Booking #{$booking->id} at {$restaurant} was cancelled.",
            ],
            BookingNotification::EVENT_BOOKING_ARRIVED => [
                'Guest arrived',
                "Booking #{$booking->id} at {$restaurant} — guest marked arrived.",
            ],
            BookingNotification::EVENT_BOOKING_SEATED => [
                'Guest seated',
                "Booking #{$booking->id} at {$restaurant} — guest marked seated.",
            ],
            BookingNotification::EVENT_BOOKING_COMPLETED => [
                'Booking completed',
                "Booking #{$booking->id} at {$restaurant} was completed.",
            ],
            BookingNotification::EVENT_BOOKING_NO_SHOW => [
                'No-show recorded',
                "Booking #{$booking->id} at {$restaurant} was marked no-show.",
            ],
            BookingNotification::EVENT_BOOKING_ARRIVAL_REMINDER => [
                'Upcoming booking reminder',
                "Reminder: booking #{$booking->id} at {$restaurant}. Starts at {$when}.",
            ],
            default => [
                'Booking update',
                "Booking #{$booking->id} event: {$event}.",
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadSnapshot(Booking $booking, string $event, string $resolvedTitle, string $resolvedMessage): array
    {
        return array_merge([
            'event' => $event,
            'booking_status' => $booking->status?->value,
            'restaurant_id' => $booking->restaurant_id,
            'branch_id' => $booking->branch_id,
            'starts_at' => $booking->starts_at?->toIso8601String(),
            'party_size' => $booking->party_size,
            'resolved_title' => $resolvedTitle,
            'resolved_message' => $resolvedMessage,
            'channel' => 'internal',
        ], $this->bookingSchedulePlaceholders($booking));
    }
}

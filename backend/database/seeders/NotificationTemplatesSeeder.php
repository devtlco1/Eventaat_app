<?php

namespace Database\Seeders;

use App\Models\BookingNotification;
use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplatesSeeder extends Seeder
{
    /**
     * Default internal notification templates (Phase 9B + 7B).
     *
     * Idempotent: safe to run multiple times (unique key: event).
     */
    public function run(): void
    {
        $templates = [
            BookingNotification::EVENT_BOOKING_REQUESTED => [
                'title_template' => 'Booking requested',
                'body_template' => 'Hello {{customer_name}}, booking at {{restaurant_name}} ({{branch_name}}) on {{booking_date}} at {{booking_time}} for {{party_size}} guest(s). Ref #{{booking_id}}. Status: {{booking_status}}.',
            ],
            BookingNotification::EVENT_BOOKING_ACCEPTED => [
                'title_template' => 'Booking accepted',
                'body_template' => 'Your booking at {{restaurant_name}} ({{branch_name}}) on {{booking_date}} at {{booking_time}} for {{party_size}} was accepted. Ref #{{booking_id}}.',
            ],
            BookingNotification::EVENT_BOOKING_REJECTED => [
                'title_template' => 'Booking rejected',
                'body_template' => 'Your booking request at {{restaurant_name}} ({{branch_name}}) on {{booking_date}} was rejected. Ref #{{booking_id}}.',
            ],
            BookingNotification::EVENT_BOOKING_CANCELLED => [
                'title_template' => 'Booking cancelled',
                'body_template' => 'Booking #{{booking_id}} at {{restaurant_name}} ({{branch_name}}) on {{booking_date}} was cancelled.',
            ],
            BookingNotification::EVENT_BOOKING_ARRIVED => [
                'title_template' => 'Guest arrived',
                'body_template' => 'Arrival recorded for booking #{{booking_id}} at {{restaurant_name}} ({{branch_name}}).',
            ],
            BookingNotification::EVENT_BOOKING_SEATED => [
                'title_template' => 'Guest seated',
                'body_template' => 'Seating recorded for booking #{{booking_id}} at {{restaurant_name}} ({{branch_name}}).',
            ],
            BookingNotification::EVENT_BOOKING_COMPLETED => [
                'title_template' => 'Booking completed',
                'body_template' => 'Booking #{{booking_id}} at {{restaurant_name}} ({{branch_name}}) was completed. Thank you.',
            ],
            BookingNotification::EVENT_BOOKING_NO_SHOW => [
                'title_template' => 'No-show recorded',
                'body_template' => 'Booking #{{booking_id}} at {{restaurant_name}} ({{branch_name}}) was marked no-show.',
            ],
            BookingNotification::EVENT_BOOKING_ARRIVAL_REMINDER => [
                'title_template' => 'Reminder: upcoming booking',
                'body_template' => 'Reminder: booking #{{booking_id}} at {{restaurant_name}} ({{branch_name}}) on {{booking_date}} at {{booking_time}} for {{party_size}}. Status: {{booking_status}}. (No auto-send yet — template ready for future scheduler.)',
            ],
        ];

        foreach ($templates as $event => $copy) {
            NotificationTemplate::updateOrCreate(
                ['event' => $event],
                [
                    'channel' => 'internal',
                    'locale' => 'en',
                    'title_template' => $copy['title_template'],
                    'body_template' => $copy['body_template'],
                    'is_active' => true,
                    'notes' => 'Seeded default template (Phase 7B).',
                ],
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\BookingNotification;
use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplatesSeeder extends Seeder
{
    /**
     * Phase 9B default notification templates.
     *
     * Idempotent: safe to run multiple times.
     */
    public function run(): void
    {
        $templates = [
            BookingNotification::EVENT_BOOKING_CREATED => [
                'title_template' => 'Booking requested',
                'body_template' => 'A new booking was requested at {{restaurant_name}} ({{branch_name}}). Starts at {{starts_at}} for {{party_size}}. Booking #{{booking_id}}.',
            ],
            BookingNotification::EVENT_BOOKING_ACCEPTED => [
                'title_template' => 'Booking accepted',
                'body_template' => 'Your booking at {{restaurant_name}} ({{branch_name}}) has been accepted. Starts at {{starts_at}} for {{party_size}}. Booking #{{booking_id}}.',
            ],
            BookingNotification::EVENT_BOOKING_REJECTED => [
                'title_template' => 'Booking rejected',
                'body_template' => 'Your booking request at {{restaurant_name}} ({{branch_name}}) was rejected. Booking #{{booking_id}}.',
            ],
            BookingNotification::EVENT_BOOKING_CANCELLED => [
                'title_template' => 'Booking cancelled',
                'body_template' => 'Booking #{{booking_id}} at {{restaurant_name}} ({{branch_name}}) was cancelled.',
            ],
            BookingNotification::EVENT_BOOKING_ARRIVED => [
                'title_template' => 'Guest arrived',
                'body_template' => 'Arrival confirmed for booking #{{booking_id}} at {{restaurant_name}} ({{branch_name}}).',
            ],
            BookingNotification::EVENT_BOOKING_SEATED => [
                'title_template' => 'Guest seated',
                'body_template' => 'Seating confirmed for booking #{{booking_id}} at {{restaurant_name}} ({{branch_name}}).',
            ],
            BookingNotification::EVENT_BOOKING_COMPLETED => [
                'title_template' => 'Booking completed',
                'body_template' => 'Booking #{{booking_id}} at {{restaurant_name}} ({{branch_name}}) was completed. Thank you, {{customer_name}}.',
            ],
            BookingNotification::EVENT_BOOKING_NO_SHOW => [
                'title_template' => 'No-show recorded',
                'body_template' => 'Booking #{{booking_id}} at {{restaurant_name}} ({{branch_name}}) was marked no-show.',
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
                    'notes' => 'Seeded default template (Phase 9B).',
                ],
            );
        }
    }
}


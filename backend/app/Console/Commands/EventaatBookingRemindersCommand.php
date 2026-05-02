<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingNotification;
use App\Services\Notifications\BookingNotificationService;
use Illuminate\Console\Command;

class EventaatBookingRemindersCommand extends Command
{
    protected $signature = 'eventaat:booking-reminders';

    protected $description = 'Record internal arrival-reminder notifications for accepted bookings starting soon (no outbound send).';

    public function handle(BookingNotificationService $bookingNotifications): int
    {
        $hours = (float) config('eventaat-notifications.booking_reminder_hours', 2);

        $now = now();

        $query = Booking::query()
            ->where('status', BookingStatus::Accepted)
            ->where('starts_at', '>', $now)
            ->where('starts_at', '<=', $now->copy()->addHours($hours))
            ->whereDoesntHave('bookingNotifications', function ($q): void {
                $q->where('event', BookingNotification::EVENT_BOOKING_ARRIVAL_REMINDER);
            });

        $count = 0;

        foreach ($query->cursor() as $booking) {
            $bookingNotifications->record($booking, BookingNotification::EVENT_BOOKING_ARRIVAL_REMINDER);
            $count++;
        }

        $this->info("Recorded {$count} arrival reminder notification(s).");

        return self::SUCCESS;
    }
}

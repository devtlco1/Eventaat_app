<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BookingRemindersScheduleRegisteredTest extends TestCase
{
    public function test_schedule_list_output_includes_booking_reminders_command(): void
    {
        Artisan::call('schedule:list');

        $this->assertStringContainsString('eventaat:booking-reminders', Artisan::output());
    }
}

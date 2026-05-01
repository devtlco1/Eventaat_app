<?php

namespace Tests\Feature;

use App\Models\NotificationTemplate;
use Database\Seeders\NotificationTemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTemplatesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_notification_templates_are_seeded_idempotently(): void
    {
        $this->seed(NotificationTemplatesSeeder::class);
        $this->assertSame(8, NotificationTemplate::count());

        $this->seed(NotificationTemplatesSeeder::class);
        $this->assertSame(8, NotificationTemplate::count());
    }
}


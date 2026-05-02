<?php

namespace Tests\Unit;

use App\Services\Notifications\NotificationTemplateRenderer;
use PHPUnit\Framework\TestCase;

class NotificationTemplateRendererTest extends TestCase
{
    public function test_renders_known_placeholders(): void
    {
        $renderer = new NotificationTemplateRenderer;

        $out = $renderer->render(
            'Hello {{customer_name}} (#{{booking_id}})',
            ['customer_name' => 'Alice', 'booking_id' => 10],
        );

        $this->assertSame('Hello Alice (#10)', $out);
    }

    public function test_unknown_placeholder_remains_unchanged_and_does_not_crash(): void
    {
        $renderer = new NotificationTemplateRenderer;

        $out = $renderer->render(
            'Hello {{unknown_key}} {{customer_name}}',
            ['customer_name' => 'Alice'],
        );

        $this->assertSame('Hello {{unknown_key}} Alice', $out);
    }

    public function test_renders_booking_date_and_booking_time_placeholders(): void
    {
        $renderer = new NotificationTemplateRenderer;

        $out = $renderer->render(
            '{{booking_date}} {{booking_time}} — {{party_size}} pax',
            ['booking_date' => '2026-06-15', 'booking_time' => '19:45', 'party_size' => 4],
        );

        $this->assertSame('2026-06-15 19:45 — 4 pax', $out);
    }
}

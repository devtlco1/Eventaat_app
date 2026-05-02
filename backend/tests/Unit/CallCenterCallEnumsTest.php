<?php

namespace Tests\Unit;

use App\Enums\CallCenterCallDirection;
use App\Enums\CallCenterCallOutcome;
use App\Enums\CallCenterCallReason;
use PHPUnit\Framework\TestCase;

class CallCenterCallEnumsTest extends TestCase
{
    public function test_direction_labels_and_colors(): void
    {
        $this->assertSame('Inbound', CallCenterCallDirection::Inbound->label());
        $this->assertSame('outbound', CallCenterCallDirection::Outbound->value);
        $this->assertSame('info', CallCenterCallDirection::Inbound->filamentColor());
        $this->assertSame('gray', CallCenterCallDirection::Outbound->filamentColor());
    }

    public function test_reason_labels_and_colors(): void
    {
        $this->assertSame('Billing', CallCenterCallReason::Billing->label());
        $this->assertSame('Complaint', CallCenterCallReason::Complaint->label());
        $this->assertSame('danger', CallCenterCallReason::Complaint->filamentColor());
        $this->assertSame('general', CallCenterCallReason::General->value);
    }

    public function test_outcome_labels_and_colors(): void
    {
        $this->assertSame('Pending', CallCenterCallOutcome::Pending->label());
        $this->assertSame('No answer', CallCenterCallOutcome::NoAnswer->label());
        $this->assertSame('warning', CallCenterCallOutcome::Pending->filamentColor());
        $this->assertSame('success', CallCenterCallOutcome::Resolved->filamentColor());
        $this->assertSame('danger', CallCenterCallOutcome::Escalated->filamentColor());
    }
}

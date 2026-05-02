<?php

namespace Tests\Unit;

use App\Enums\RestaurantInvoiceStatus;
use PHPUnit\Framework\TestCase;

class RestaurantInvoiceStatusEnumTest extends TestCase
{
    public function test_labels_and_filament_colors(): void
    {
        $this->assertSame('Draft', RestaurantInvoiceStatus::Draft->label());
        $this->assertSame('Void', RestaurantInvoiceStatus::Void->label());
        $this->assertSame('paid', RestaurantInvoiceStatus::Paid->value);
        $this->assertSame('warning', RestaurantInvoiceStatus::Overdue->filamentColor());
        $this->assertSame('success', RestaurantInvoiceStatus::Paid->filamentColor());
        $this->assertSame('danger', RestaurantInvoiceStatus::Void->filamentColor());
    }
}

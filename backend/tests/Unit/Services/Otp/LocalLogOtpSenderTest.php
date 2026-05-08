<?php

namespace Tests\Unit\Services\Otp;

use App\Services\Otp\LocalLogOtpSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LocalLogOtpSenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_code_is_included_in_log_for_local_and_testing_environments(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return str_contains($message, 'Mobile OTP')
                    && ($context['otp'] ?? null) === '654321';
            });

        $sender = new LocalLogOtpSender;
        $sender->send('+9647700001781', '654321');
    }

    public function test_phone_is_masked_in_log_and_not_stored_in_plaintext(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return isset($context['phone_masked'])
                    && ! str_contains($context['phone_masked'], '+9647700001781')
                    && str_ends_with($context['phone_masked'], '1781');
            });

        $sender = new LocalLogOtpSender;
        $sender->send('+9647700001781', '123456');
    }

    public function test_delivery_attempt_row_is_recorded_with_local_log_driver(): void
    {
        Log::shouldReceive('info')->once();

        $sender = new LocalLogOtpSender;
        $sender->send('+9647700001781', '999888');

        $this->assertDatabaseHas('otp_delivery_attempts', [
            'driver'   => 'log',
            'channel'  => 'log',
            'provider' => 'local_log',
            'status'   => 'sent',
        ]);
    }
}

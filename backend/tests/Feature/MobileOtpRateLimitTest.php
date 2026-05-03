<?php

namespace Tests\Feature;

use App\Models\MobileOtp;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileOtpRateLimitTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'eventaat-notifications.otp.driver' => 'log',
        ]);

        $this->seed(RolesAndTestUsersSeeder::class);
    }

    public function test_second_otp_request_within_cooldown_is_rate_limited(): void
    {
        config([
            'eventaat-notifications.otp_rate_limit.request_cooldown_seconds' => 60,
            'eventaat-notifications.otp_rate_limit.request_max_per_hour' => 10,
        ]);

        $phone = '+15559876501';

        $this->postJson('/api/mobile/auth/request-otp', ['phone' => $phone])->assertOk();

        $this->postJson('/api/mobile/auth/request-otp', ['phone' => $phone])
            ->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);
    }

    public function test_otp_request_after_cooldown_succeeds(): void
    {
        config([
            'eventaat-notifications.otp_rate_limit.request_cooldown_seconds' => 60,
            'eventaat-notifications.otp_rate_limit.request_max_per_hour' => 10,
        ]);

        $phone = '+15559876502';

        $this->postJson('/api/mobile/auth/request-otp', ['phone' => $phone])->assertOk();

        $this->travel(61)->seconds();

        $this->postJson('/api/mobile/auth/request-otp', ['phone' => $phone])->assertOk();
    }

    public function test_hourly_otp_request_cap_returns_429(): void
    {
        config([
            'eventaat-notifications.otp_rate_limit.request_cooldown_seconds' => 0,
            'eventaat-notifications.otp_rate_limit.request_max_per_hour' => 5,
        ]);

        $phone = '+15559876503';

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/mobile/auth/request-otp', ['phone' => $phone])->assertOk();
        }

        $this->postJson('/api/mobile/auth/request-otp', ['phone' => $phone])
            ->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);
    }

    public function test_verify_lockout_after_failed_attempts_returns_429(): void
    {
        config([
            'eventaat-notifications.otp_rate_limit.verify_max_attempts' => 5,
            'eventaat-notifications.otp_rate_limit.verify_decay_minutes' => 10,
        ]);

        $phone = '+15559876504';

        MobileOtp::create([
            'phone' => $phone,
            'otp_hash' => Hash::make('999999'),
            'expires_at' => Carbon::now()->addMinutes(5),
            'attempts' => 0,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/mobile/auth/verify-otp', [
                'phone' => $phone,
                'otp' => '000000',
            ])->assertStatus(422);
        }

        $this->postJson('/api/mobile/auth/verify-otp', [
            'phone' => $phone,
            'otp' => '999999',
        ])
            ->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);
    }

    public function test_successful_verify_clears_verify_failure_bucket(): void
    {
        config([
            'eventaat-notifications.otp_rate_limit.verify_max_attempts' => 5,
            'eventaat-notifications.otp_rate_limit.verify_decay_minutes' => 10,
        ]);

        $phone = '+15559876505';

        MobileOtp::create([
            'phone' => $phone,
            'otp_hash' => Hash::make('654321'),
            'expires_at' => Carbon::now()->addMinutes(5),
            'attempts' => 0,
        ]);

        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/mobile/auth/verify-otp', [
                'phone' => $phone,
                'otp' => '111111',
            ])->assertStatus(422);
        }

        $this->postJson('/api/mobile/auth/verify-otp', [
            'phone' => $phone,
            'otp' => '654321',
        ])->assertOk();

        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/mobile/auth/verify-otp', [
                'phone' => $phone,
                'otp' => '222222',
            ])->assertStatus(422);
        }

        $this->postJson('/api/mobile/auth/verify-otp', [
            'phone' => $phone,
            'otp' => '654321',
        ])->assertStatus(422);
    }
}

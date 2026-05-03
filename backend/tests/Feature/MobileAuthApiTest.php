<?php

namespace Tests\Feature;

use App\Models\MobileOtp;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileAuthApiTest extends TestCase
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

    public function test_request_otp_accepts_valid_e164_international_phone_with_log_driver(): void
    {
        $phone = '+9647700001781';

        $this->postJson('/api/mobile/auth/request-otp', ['phone' => $phone])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('mobile_otps', ['phone' => $phone]);
    }

    public function test_request_otp_rejects_phone_with_non_digit_placeholders(): void
    {
        $this->postJson('/api/mobile/auth/request-otp', ['phone' => '+9647XXXXXXXXX'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_request_otp_rejects_too_short_e164_phone(): void
    {
        $this->postJson('/api/mobile/auth/request-otp', ['phone' => '+1234567'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_request_otp_rejects_phone_without_leading_plus(): void
    {
        $this->postJson('/api/mobile/auth/request-otp', ['phone' => '9647700001781'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_request_otp_rejects_invalid_phone_with_letters(): void
    {
        $this->postJson('/api/mobile/auth/request-otp', ['phone' => 'abc'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_request_otp_creates_or_updates_record(): void
    {
        $phone = '+15550000001';

        $this->postJson('/api/mobile/auth/request-otp', ['phone' => $phone])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('mobile_otps', ['phone' => $phone]);

        $first = MobileOtp::where('phone', $phone)->firstOrFail();

        $this->travel(61)->seconds();

        $this->postJson('/api/mobile/auth/request-otp', ['phone' => $phone])->assertOk();
        $second = MobileOtp::where('phone', $phone)->latest('id')->firstOrFail();

        $this->assertTrue($second->id >= $first->id);
    }

    public function test_new_customer_can_verify_otp_without_name_and_gets_profile_incomplete(): void
    {
        $phone = '+15550000002';

        MobileOtp::create([
            'phone' => $phone,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => Carbon::now()->addMinutes(5),
            'attempts' => 0,
        ]);

        $resp = $this->postJson('/api/mobile/auth/verify-otp', [
            'phone' => $phone,
            'otp' => '123456',
        ])->assertOk();

        $resp->assertJsonPath('me.phone', $phone);
        $resp->assertJsonPath('me.role', 'customer');
        $resp->assertJsonPath('me.profile_completed', false);
        $resp->assertJsonPath('me.missing_fields.0', 'name');
        $resp->assertJsonStructure(['token', 'me' => ['id', 'name', 'phone', 'role', 'profile_completed', 'missing_fields']]);

        $user = User::where('phone', $phone)->firstOrFail();
        $this->assertTrue($user->hasRole('customer'));
    }

    public function test_customer_can_update_name_and_me_returns_name_and_phone(): void
    {
        $phone = '+15550000003';

        $user = User::create([
            'name' => '',
            'phone' => $phone,
            'email' => 'mobile_test3@mobile.eventaat.test',
            'password' => Hash::make('x'),
        ]);
        $user->syncRoles(['customer']);

        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)->getJson('/api/mobile/me')
            ->assertOk()
            ->assertJsonPath('phone', $phone);

        $this->withToken($token)->patchJson('/api/mobile/me', ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('me.name', 'New Name')
            ->assertJsonPath('me.profile_completed', true);

        $this->withToken($token)->getJson('/api/mobile/me')
            ->assertOk()
            ->assertJsonPath('name', 'New Name')
            ->assertJsonPath('profile_completed', true);
    }

    public function test_me_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/mobile/me')->assertStatus(401);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::where('email', 'customer@eventaat.test')->firstOrFail();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)->postJson('/api/mobile/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->withToken($token)->getJson('/api/mobile/me')->assertStatus(401);
    }

    public function test_phone_is_unique_for_mobile_users(): void
    {
        $phone = '+15550000004';

        User::create([
            'name' => '',
            'phone' => $phone,
            'email' => 'mobile_unique1@mobile.eventaat.test',
            'password' => Hash::make('x'),
        ])->syncRoles(['customer']);

        $this->expectException(QueryException::class);

        User::create([
            'name' => '',
            'phone' => $phone,
            'email' => 'mobile_unique2@mobile.eventaat.test',
            'password' => Hash::make('x'),
        ]);
    }

    public function test_customer_still_cannot_access_filament_panels(): void
    {
        $user = User::where('email', 'customer@eventaat.test')->firstOrFail();
        $this->actingAs($user);

        $this->get('/platform')->assertForbidden();
        $this->get('/restaurant')->assertForbidden();
    }
}

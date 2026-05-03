<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\RequestOtpRequest;
use App\Http\Requests\Mobile\VerifyOtpRequest;
use App\Http\Resources\Mobile\MeResource;
use App\Models\User;
use App\Services\Otp\MobileOtpRateLimiter;
use App\Services\Otp\MobileOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(
        private readonly MobileOtpService $otp,
        private readonly MobileOtpRateLimiter $otpRateLimiter,
    ) {}

    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->toString();

        if ($limited = $this->otpRateLimiter->responseIfRequestLimited($phone)) {
            return $limited;
        }

        $record = $this->otp->request($phone);

        $this->otpRateLimiter->hitSuccessfulRequest($phone);

        return response()->json([
            'success' => true,
            'expires_at' => $record->expires_at?->toISOString(),
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->toString();
        $otp = $request->string('otp')->toString();
        $name = $request->string('name')->toString();
        $name = trim($name);

        if ($limited = $this->otpRateLimiter->responseIfVerifyLimited($phone)) {
            return $limited;
        }

        $verified = $this->otp->verify($phone, $otp);
        if (! $verified) {
            $this->otpRateLimiter->hitVerifyFailure($phone);

            return response()->json([
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        $this->otpRateLimiter->clearVerifyFailures($phone);

        $user = User::query()->where('phone', $phone)->first();

        if ($user && ! $user->hasRole('customer')) {
            return response()->json([
                'message' => 'This phone number cannot be used for customer login.',
            ], 409);
        }

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'phone' => $phone,
                'email' => MobileOtpService::mobileEmailForPhone($phone),
                'password' => Hash::make(Str::random(32)),
            ]);
        } else {
            if ($name !== '' && trim((string) $user->name) === '') {
                $user->forceFill(['name' => $name])->save();
            }
        }

        // Mobile-created users must always be customer only.
        $user->syncRoles(['customer']);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'me' => (new MeResource($user))->toArray($request),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke the current token (and any other mobile tokens) for safety.
        // This stays within Phase 3 scope and keeps behavior predictable.
        if ($user) {
            PersonalAccessToken::query()
                ->where('tokenable_type', $user::class)
                ->where('tokenable_id', $user->id)
                ->delete();
        }

        return response()->json([
            'success' => true,
        ]);
    }
}

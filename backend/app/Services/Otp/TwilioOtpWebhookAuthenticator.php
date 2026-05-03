<?php

namespace App\Services\Otp;

use Illuminate\Http\Request;
use Twilio\Security\RequestValidator;

final class TwilioOtpWebhookAuthenticator
{
    /**
     * Validates Twilio request signature when TWILIO_AUTH_TOKEN is set.
     * Otherwise validates X-Eventaat-Webhook-Secret against TWILIO_WEBHOOK_SECRET when set.
     * Returns false when neither credential path can authenticate (reject with 403).
     */
    public function authenticate(Request $request): bool
    {
        $twilio = config('eventaat-notifications.twilio', []);
        $authToken = trim((string) ($twilio['auth_token'] ?? ''));
        $webhookSecret = trim((string) ($twilio['webhook_secret'] ?? ''));

        if ($authToken !== '') {
            $signature = (string) $request->header('X-Twilio-Signature', '');

            $validator = new RequestValidator($authToken);

            return $validator->validate($signature, $request->fullUrl(), $request->request->all());
        }

        if ($webhookSecret !== '') {
            $provided = (string) $request->header('X-Eventaat-Webhook-Secret', '');

            return hash_equals($webhookSecret, $provided);
        }

        return false;
    }
}

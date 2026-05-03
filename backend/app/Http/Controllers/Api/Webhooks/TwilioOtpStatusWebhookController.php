<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Otp\OtpDeliveryStatusService;
use App\Services\Otp\TwilioOtpWebhookAuthenticator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TwilioOtpStatusWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TwilioOtpWebhookAuthenticator $authenticator,
        OtpDeliveryStatusService $statusService,
    ): Response {
        if (! $authenticator->authenticate($request)) {
            abort(403);
        }

        $statusService->handleCallback($request->request->all());

        return response('', 200);
    }
}

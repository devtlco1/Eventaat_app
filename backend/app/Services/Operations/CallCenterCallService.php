<?php

namespace App\Services\Operations;

use App\Enums\CallCenterCallOutcome;
use App\Models\CallCenterCall;

class CallCenterCallService
{
    public function markResolved(CallCenterCall $call): void
    {
        $call->forceFill([
            'outcome' => CallCenterCallOutcome::Resolved,
            'completed_at' => $call->completed_at ?? now(),
        ])->save();
    }

    public function markEscalated(CallCenterCall $call): void
    {
        $call->forceFill([
            'outcome' => CallCenterCallOutcome::Escalated,
        ])->save();
    }

    public function markNoAnswer(CallCenterCall $call): void
    {
        $call->forceFill([
            'outcome' => CallCenterCallOutcome::NoAnswer,
        ])->save();
    }
}

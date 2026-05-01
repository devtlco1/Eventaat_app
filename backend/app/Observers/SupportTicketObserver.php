<?php

namespace App\Observers;

use App\Models\SupportTicket;
use App\Models\User;
use App\Services\SupportTicketActivityService;
use Illuminate\Support\Facades\Auth;

class SupportTicketObserver
{
    /**
     * Log status transitions after update. Uses {@see SupportTicket::getChanges()}
     * and {@see SupportTicket::getPrevious()} because Laravel clears dirty state before listeners run.
     */
    public function updated(SupportTicket $ticket): void
    {
        $changes = $ticket->getChanges();

        if (! array_key_exists('status', $changes)) {
            return;
        }

        $newStatus = $changes['status'];
        $previous = $ticket->getPrevious();
        $oldStatus = array_key_exists('status', $previous) ? $previous['status'] : null;

        if ($oldStatus === $newStatus) {
            return;
        }

        /** @var SupportTicketActivityService $service */
        $service = app(SupportTicketActivityService::class);

        $actor = Auth::user();
        $actor = $actor instanceof User ? $actor : null;

        $service->recordStatusChange($ticket, $actor, $oldStatus, $newStatus);
    }
}

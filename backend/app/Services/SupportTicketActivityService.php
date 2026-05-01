<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\SupportTicketActivity;
use App\Models\User;

class SupportTicketActivityService
{
    public function recordNote(SupportTicket $ticket, User $user, string $message): SupportTicketActivity
    {
        return SupportTicketActivity::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'type' => SupportTicketActivity::TYPE_NOTE,
            'message' => $message,
            'is_internal' => true,
        ]);
    }

    public function recordStatusChange(SupportTicket $ticket, ?User $user, ?string $oldStatus, string $newStatus): SupportTicketActivity
    {
        if ($oldStatus === $newStatus) {
            throw new \InvalidArgumentException('Status change activity requires different old and new status.');
        }

        return SupportTicketActivity::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'type' => SupportTicketActivity::TYPE_STATUS_CHANGE,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'message' => null,
            'is_internal' => true,
        ]);
    }
}

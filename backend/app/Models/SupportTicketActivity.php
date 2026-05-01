<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketActivity extends Model
{
    public const TYPE_NOTE = 'note';

    public const TYPE_STATUS_CHANGE = 'status_change';

    public const TYPE_SYSTEM = 'system';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_NOTE,
        self::TYPE_STATUS_CHANGE,
        self::TYPE_SYSTEM,
    ];

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'type',
        'old_status',
        'new_status',
        'message',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'support_ticket_id' => 'integer',
            'user_id' => 'integer',
            'is_internal' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

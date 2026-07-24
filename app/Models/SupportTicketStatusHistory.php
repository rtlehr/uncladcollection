<?php

namespace App\Models;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketStatusHistory extends Model
{
    protected $fillable = [
        'support_ticket_id', 'actor_id', 'event_type', 'from_status', 'to_status',
        'from_priority', 'to_priority', 'from_assigned_user_id', 'to_assigned_user_id',
        'public_note', 'internal_note',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => SupportTicketStatus::class,
            'to_status' => SupportTicketStatus::class,
            'from_priority' => SupportTicketPriority::class,
            'to_priority' => SupportTicketPriority::class,
        ];
    }

    public function ticket(): BelongsTo { return $this->belongsTo(SupportTicket::class, 'support_ticket_id'); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
    public function fromAssignee(): BelongsTo { return $this->belongsTo(User::class, 'from_assigned_user_id'); }
    public function toAssignee(): BelongsTo { return $this->belongsTo(User::class, 'to_assigned_user_id'); }
}

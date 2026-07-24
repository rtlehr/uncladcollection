<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupportTicketRelation extends Model
{
    protected $fillable = ['support_ticket_id', 'related_type', 'related_id', 'label', 'added_by'];

    public function ticket(): BelongsTo { return $this->belongsTo(SupportTicket::class, 'support_ticket_id'); }
    public function related(): MorphTo { return $this->morphTo(); }
    public function addedBy(): BelongsTo { return $this->belongsTo(User::class, 'added_by'); }
}

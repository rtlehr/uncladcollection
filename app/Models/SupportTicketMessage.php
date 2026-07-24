<?php

namespace App\Models;

use App\Enums\SupportTicketMessageType;
use Database\Factories\SupportTicketMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicketMessage extends Model
{
    /** @use HasFactory<SupportTicketMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'support_ticket_id', 'user_id', 'author_name', 'author_email',
        'message_type', 'body', 'is_customer_visible', 'edited_by', 'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'message_type' => SupportTicketMessageType::class,
            'is_customer_visible' => 'boolean',
            'edited_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo { return $this->belongsTo(SupportTicket::class, 'support_ticket_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function editor(): BelongsTo { return $this->belongsTo(User::class, 'edited_by'); }
    public function attachments(): HasMany { return $this->hasMany(SupportTicketAttachment::class); }
}

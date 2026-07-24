<?php

namespace App\Models;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketSource;
use App\Enums\SupportTicketStatus;
use Database\Factories\SupportTicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    /** @use HasFactory<SupportTicketFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'ticket_number', 'user_id', 'guest_name', 'guest_email',
        'guest_access_token_hash', 'category_id', 'assigned_user_id', 'status',
        'priority', 'source', 'subject', 'description', 'last_customer_reply_at',
        'last_staff_reply_at', 'first_response_at', 'resolved_at', 'closed_at',
        'resolution_summary',
    ];

    protected $hidden = ['guest_access_token_hash'];

    protected function casts(): array
    {
        return [
            'status' => SupportTicketStatus::class,
            'priority' => SupportTicketPriority::class,
            'source' => SupportTicketSource::class,
            'last_customer_reply_at' => 'datetime',
            'last_staff_reply_at' => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(SupportTicketCategory::class, 'category_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_user_id'); }
    public function messages(): HasMany { return $this->hasMany(SupportTicketMessage::class); }
    public function customerVisibleMessages(): HasMany { return $this->messages()->where('is_customer_visible', true); }
    public function attachments(): HasMany { return $this->hasMany(SupportTicketAttachment::class); }
    public function relations(): HasMany { return $this->hasMany(SupportTicketRelation::class); }
    public function statusHistories(): HasMany { return $this->hasMany(SupportTicketStatusHistory::class); }

    public function isGuestTicket(): bool { return $this->user_id === null; }
    public function isOwnedBy(User $user): bool { return $this->user_id === $user->id; }
}

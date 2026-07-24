<?php

namespace App\Models;

use App\Enums\SupportAttachmentScanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketAttachment extends Model
{
    protected $fillable = [
        'uuid', 'support_ticket_id', 'support_ticket_message_id', 'uploaded_by',
        'disk', 'path', 'original_filename', 'mime_type', 'extension', 'size_bytes',
        'checksum_sha256', 'scan_status', 'is_customer_visible',
    ];

    protected function casts(): array
    {
        return [
            'scan_status' => SupportAttachmentScanStatus::class,
            'size_bytes' => 'integer',
            'is_customer_visible' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo { return $this->belongsTo(SupportTicket::class, 'support_ticket_id'); }
    public function message(): BelongsTo { return $this->belongsTo(SupportTicketMessage::class, 'support_ticket_message_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}

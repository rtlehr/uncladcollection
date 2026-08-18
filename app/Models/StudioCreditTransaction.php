<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudioCreditTransaction extends Model
{
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_COMPLIMENTARY = 'complimentary';
    public const TYPE_EXPORT = 'export';
    public const TYPE_PROMOTION = 'promotion';
    public const TYPE_REFUND = 'refund';

    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED = 'posted';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'uuid', 'user_id', 'design_export_id', 'license_id', 'studio_credit_package_id',
        'type', 'status', 'credits', 'amount_cents', 'currency',
        'stripe_checkout_session_id', 'stripe_payment_intent_id', 'metadata',
        'posted_at', 'voided_at',
    ];

    protected $casts = [
        'credits' => 'integer',
        'amount_cents' => 'integer',
        'metadata' => 'array',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $transaction) => $transaction->uuid ??= (string) Str::uuid());
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function export(): BelongsTo { return $this->belongsTo(DesignExport::class, 'design_export_id'); }
    public function license(): BelongsTo { return $this->belongsTo(License::class); }
    public function package(): BelongsTo { return $this->belongsTo(StudioCreditPackage::class, 'studio_credit_package_id'); }
}

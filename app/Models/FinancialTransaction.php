<?php

namespace App\Models;

use App\Enums\FinancialTransactionStatus;
use App\Enums\FinancialTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    protected $fillable = [
        'order_id', 'advertising_invoice_id', 'advertising_payment_id', 'type', 'status', 'amount_cents', 'currency', 'provider',
        'provider_reference', 'reason', 'notes', 'metadata', 'occurred_at',
    ];

    protected $casts = [
        'type' => FinancialTransactionType::class,
        'status' => FinancialTransactionStatus::class,
        'amount_cents' => 'integer',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function advertisingInvoice(): BelongsTo
    {
        return $this->belongsTo(AdvertisingInvoice::class);
    }

    public function advertisingPayment(): BelongsTo
    {
        return $this->belongsTo(AdvertisingPayment::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

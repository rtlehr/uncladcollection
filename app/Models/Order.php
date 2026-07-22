<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\OrderFulfillmentStatus;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    public const PAYMENT_PROVIDER_MANUAL = 'manual';
    public const PAYMENT_PROVIDER_STRIPE = 'stripe';

    protected $fillable = [
        'order_number', 'commerce_version', 'user_id', 'status', 'fulfillment_status',
        'subtotal_cents', 'discount_cents', 'tax_cents', 'total_cents', 'currency',
        'payment_provider', 'payment_reference', 'stripe_checkout_session_id',
        'stripe_payment_intent_id', 'paid_at', 'refunded_at', 'canceled_at',
        'checkout_locked_at', 'shipping_carrier', 'tracking_number', 'fulfillment_notes',
        'shipped_at', 'delivered_at', 'fulfilled_at', 'notes', 'metadata', 'checkout_snapshot',
    ];

    protected $casts = [
        'subtotal_cents' => 'integer',
        'discount_cents' => 'integer',
        'tax_cents' => 'integer',
        'total_cents' => 'integer',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'canceled_at' => 'datetime',
        'checkout_locked_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'fulfillment_status' => OrderFulfillmentStatus::class,
        'metadata' => 'array',
        'checkout_snapshot' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (blank($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'UC-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function licenses(): HasMany { return $this->hasMany(License::class); }
    public function fulfillmentEvents(): HasMany { return $this->hasMany(OrderFulfillmentEvent::class)->orderBy('created_at'); }
    public function financialTransactions(): HasMany { return $this->hasMany(FinancialTransaction::class)->orderBy('occurred_at'); }
    public function getSubtotalFormattedAttribute(): string { return '$'.number_format($this->subtotal_cents / 100, 2); }
    public function getTotalFormattedAttribute(): string { return '$'.number_format($this->total_cents / 100, 2); }
    public function isPaid(): bool { return $this->status === self::STATUS_PAID; }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class License extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'user_id',
        'image_id',
        'order_id',
        'order_item_id',
        'license_type_id',
        'license_key',
        'status',
        'starts_at',
        'expires_at',
        'download_limit',
        'downloads_used',
        'license_name',
        'license_terms',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
        'download_limit' => 'integer',
        'downloads_used' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (License $license) {
            if (blank($license->license_key)) {
                $license->license_key = 'LIC-' . strtoupper(bin2hex(random_bytes(8)));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function licenseType(): BelongsTo
    {
        return $this->belongsTo(LicenseType::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->expires_at && now()->greaterThan($this->expires_at)) {
            return false;
        }

        return true;
    }

    public function canDownload(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if (
            $this->download_limit !== null &&
            $this->downloads_used >= $this->download_limit
        ) {
            return false;
        }

        return true;
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

}
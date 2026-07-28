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
        'user_id', 'image_id', 'asset_id', 'order_id', 'order_item_id',
        'license_type_id', 'asset_offering_id', 'license_key', 'status', 'status_reason', 'status_changed_at',
        'fulfillment_type', 'commerce_version', 'starts_at', 'expires_at',
        'download_limit', 'downloads_used', 'license_name', 'license_terms', 'terms_version',
        'included_asset_files_snapshot', 'configuration_snapshot', 'pricing_snapshot',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'status_changed_at' => 'datetime',
        'metadata' => 'array',
        'included_asset_files_snapshot' => 'array',
        'configuration_snapshot' => 'array',
        'pricing_snapshot' => 'array',
        'download_limit' => 'integer',
        'downloads_used' => 'integer',
        'terms_version' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (License $license): void {
            if (blank($license->license_key)) {
                $license->license_key = 'LIC-'.strtoupper(bin2hex(random_bytes(8)));
            }
        });
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function image(): BelongsTo { return $this->belongsTo(Image::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function licenseType(): BelongsTo { return $this->belongsTo(LicenseType::class); }
    public function assetOffering(): BelongsTo { return $this->belongsTo(AssetOffering::class); }
    public function downloads(): HasMany { return $this->hasMany(Download::class); }
    public function statusHistories(): HasMany { return $this->hasMany(LicenseStatusHistory::class)->latest(); }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && (! $this->expires_at || now()->lessThan($this->expires_at));
    }

    public function canDownload(): bool
    {
        return $this->isActive()
            && ($this->download_limit === null || $this->downloads_used < $this->download_limit);
    }
}

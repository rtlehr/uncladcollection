<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    protected $fillable = [
        'user_id', 'image_id', 'asset_id', 'asset_file_id', 'license_id',
        'order_item_id', 'batch_uuid', 'download_type', 'source',
        'original_filename', 'size_bytes', 'status', 'failure_reason',
        'ip_address', 'user_agent', 'downloaded_at',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
        'size_bytes' => 'integer',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function image(): BelongsTo { return $this->belongsTo(Image::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function assetFile(): BelongsTo { return $this->belongsTo(AssetFile::class); }
    public function license(): BelongsTo { return $this->belongsTo(License::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
}

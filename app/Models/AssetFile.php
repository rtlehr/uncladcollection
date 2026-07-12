<?php

namespace App\Models;

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileRole;
use App\Enums\AssetFileScanStatus;
use App\Enums\AssetMediaType;
use App\Observers\AssetFileObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[ObservedBy([AssetFileObserver::class])]
class AssetFile extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'asset_id', 'uuid', 'role', 'media_type', 'disk', 'directory',
        'stored_filename', 'original_filename', 'extension', 'mime_type',
        'size_bytes', 'checksum_sha256', 'sort_order', 'width', 'height',
        'duration_seconds', 'page_count', 'metadata', 'processing_status',
        'virus_scan_status', 'is_downloadable', 'is_active', 'is_legacy',
    ];

    protected function casts(): array
    {
        return [
            'role' => AssetFileRole::class,
            'media_type' => AssetMediaType::class,
            'processing_status' => AssetFileProcessingStatus::class,
            'virus_scan_status' => AssetFileScanStatus::class,
            'size_bytes' => 'integer',
            'sort_order' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration_seconds' => 'decimal:3',
            'page_count' => 'integer',
            'metadata' => 'array',
            'is_downloadable' => 'boolean',
            'is_active' => 'boolean',
            'is_legacy' => 'boolean',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function getPathAttribute(): string
    {
        return trim($this->directory.'/'.$this->stored_filename, '/');
    }

    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    public function isPubliclyAddressable(): bool
    {
        return $this->disk === 'public';
    }

    public function publicUrl(): ?string
    {
        return $this->isPubliclyAddressable()
            ? Storage::disk($this->disk)->url($this->path)
            : null;
    }

    public function isReady(): bool
    {
        return $this->processing_status === AssetFileProcessingStatus::Ready;
    }
}

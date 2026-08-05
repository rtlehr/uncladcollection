<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class AdCreative extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = ['draft', 'submitted', 'approved', 'rejected'];

    protected $fillable = [
        'uuid', 'advertising_campaign_id', 'ad_placement_id', 'name', 'creative_type', 'status',
        'media_path', 'original_media_path', 'media_edit_data', 'mime_type', 'original_filename',
        'file_size', 'width', 'height', 'headline', 'body', 'cta_label', 'destination_url',
        'alt_text', 'submitted_at', 'approved_at', 'approved_by', 'rejection_reason',
    ];

    protected $casts = ['media_edit_data' => 'array', 'submitted_at' => 'datetime', 'approved_at' => 'datetime'];
    protected $appends = ['media_url', 'original_media_url', 'is_placement_compatible'];

    public function campaign(): BelongsTo { return $this->belongsTo(AdvertisingCampaign::class, 'advertising_campaign_id'); }
    public function placement(): BelongsTo { return $this->belongsTo(AdPlacement::class, 'ad_placement_id'); }
    public function placements(): BelongsToMany { return $this->belongsToMany(AdPlacement::class, 'ad_creative_placement')->withTimestamps(); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function getMediaUrlAttribute(): ?string { return $this->media_path ? Storage::disk('public')->url($this->media_path) : null; }
    public function getOriginalMediaUrlAttribute(): ?string { return $this->original_media_path ? Storage::disk('public')->url($this->original_media_path) : null; }
    public function getIsPlacementCompatibleAttribute(): bool
    {
        $placements = $this->relationLoaded('placements') ? $this->placements : $this->placements()->get();
        if ($placements->isEmpty() || ! $this->width || ! $this->height) return true;
        return $placements->every(fn (AdPlacement $placement) =>
            ! $placement->width || ! $placement->height ||
            ((int) $this->width === (int) $placement->width && (int) $this->height === (int) $placement->height)
        );
    }
}

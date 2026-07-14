<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MarketingCampaign extends Model
{
    protected $fillable = [
        'uuid', 'name', 'media_type', 'media_path', 'poster_path', 'headline',
        'subheadline', 'eyebrow', 'primary_button_label', 'primary_button_url',
        'secondary_button_label', 'secondary_button_url', 'overlay_opacity',
        'media_position', 'hero_height', 'text_alignment', 'autoplay_first_visit',
        'autoplay_mobile', 'loop_video', 'show_search', 'is_active', 'sort_order',
        'starts_at', 'ends_at',
    ];

    protected $casts = [
        'autoplay_first_visit' => 'boolean',
        'autoplay_mobile' => 'boolean',
        'loop_video' => 'boolean',
        'show_search' => 'boolean',
        'is_active' => 'boolean',
        'overlay_opacity' => 'integer',
        'sort_order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected $appends = ['media_url', 'poster_url', 'is_current'];

    public function scopeCurrent(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function getMediaUrlAttribute(): ?string
    {
        return $this->media_path ? Storage::disk('public')->url($this->media_path) : null;
    }

    public function getPosterUrlAttribute(): ?string
    {
        return $this->poster_path ? Storage::disk('public')->url($this->poster_path) : null;
    }

    public function getIsCurrentAttribute(): bool
    {
        return $this->is_active
            && ($this->starts_at === null || $this->starts_at->lte(now()))
            && ($this->ends_at === null || $this->ends_at->gte(now()));
    }
}

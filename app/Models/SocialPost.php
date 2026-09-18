<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SocialPost extends Model
{
    protected $fillable = [
        'user_id', 'text', 'status', 'media_disk', 'media_path', 'media_type',
        'scheduled_at', 'published_at', 'x_post_id', 'x_post_url',
        'error_message', 'api_response', 'attempts',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'api_response' => 'array',
        'attempts' => 'integer',
    ];

    protected $appends = ['media_url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getMediaUrlAttribute(): ?string
    {
        return $this->media_path
            ? Storage::disk($this->media_disk ?: 'public')->url($this->media_path)
            : null;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'scheduled', 'failed'], true);
    }
}

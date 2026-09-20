<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPostAiSuggestion extends Model
{
    protected $fillable = [
        'ai_generation_id', 'requested_by', 'subject', 'campaign', 'prompt_additions', 'position', 'text',
        'hashtags', 'image_suggestion', 'status', 'social_post_id', 'source_suggestion_id',
        'accepted_at', 'rejected_at',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'prompt_additions' => 'array',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function generation(): BelongsTo
    {
        return $this->belongsTo(AiGeneration::class, 'ai_generation_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    public function sourceSuggestion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_suggestion_id');
    }

    public function derivedSuggestions(): HasMany
    {
        return $this->hasMany(self::class, 'source_suggestion_id');
    }

    public function finalText(): string
    {
        $tags = collect($this->hashtags ?? [])
            ->map(fn ($tag) => '#'.ltrim(trim((string) $tag), '#'))
            ->filter(fn ($tag) => $tag !== '#')
            ->unique()
            ->implode(' ');

        return trim($this->text.($tags !== '' ? "\n\n{$tags}" : ''));
    }
}

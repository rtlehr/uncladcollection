<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageBox extends Model
{
    protected $fillable = [
        'uuid','name','title','body_html','image_path','presentation','trigger_type','trigger_key',
        'page_patterns','audience','show_once','is_dismissible','is_active','priority','starts_at','ends_at',
        'buttons','form_fields','form_submit_label','form_success_message',
    ];

    protected $casts = [
        'page_patterns' => 'array', 'buttons' => 'array', 'form_fields' => 'array',
        'show_once' => 'boolean', 'is_dismissible' => 'boolean', 'is_active' => 'boolean',
        'starts_at' => 'datetime', 'ends_at' => 'datetime',
    ];

    protected $appends = ['image_url', 'is_current'];

    protected static function booted(): void
    {
        static::creating(function (self $message): void {
            $message->uuid ??= (string) Str::uuid();
        });
    }

    public function views(): HasMany { return $this->hasMany(MessageBoxView::class); }
    public function submissions(): HasMany { return $this->hasMany(MessageBoxSubmission::class); }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function getIsCurrentAttribute(): bool
    {
        return $this->is_active && (! $this->starts_at || $this->starts_at->lte(now())) && (! $this->ends_at || $this->ends_at->gte(now()));
    }

    public function matchesPath(string $path): bool
    {
        $patterns = $this->page_patterns ?: ['*'];
        $normalized = '/'.ltrim(parse_url($path, PHP_URL_PATH) ?: '/', '/');
        foreach ($patterns as $pattern) {
            $pattern = trim((string) $pattern);
            if ($pattern === '' || $pattern === '*') return true;
            if (Str::is('/'.ltrim($pattern, '/'), $normalized)) return true;
        }
        return false;
    }
}

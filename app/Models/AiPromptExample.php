<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AiPromptExample extends Model
{
    protected $fillable = [
        'uuid',
        'title',
        'content',
        'category',
        'content_context',
        'intended_uses',
        'subject_tags',
        'is_family_friendly',
        'is_enabled',
        'usage_count',
        'last_used_at',
        'source_filename',
        'source_index',
        'created_by',
        'normalized_content',
    ];

    protected $casts = [
        'intended_uses' => 'array',
        'subject_tags' => 'array',
        'is_family_friendly' => 'boolean',
        'is_enabled' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            $model->normalized_content = self::normalize(
                (string) $model->content
            );
        });

        static::updating(function (self $model): void {
            if ($model->isDirty('content')) {
                $model->normalized_content = self::normalize(
                    (string) $model->content
                );
            }
        });
    }

    public static function normalize(string $value): string
    {
        return trim(
            preg_replace(
                '/\s+/',
                ' ',
                mb_strtolower(strip_tags($value))
            ) ?? ''
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
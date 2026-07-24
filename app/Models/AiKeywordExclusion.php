<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AiKeywordExclusion extends Model
{
    protected $fillable = ['keyword', 'normalized_keyword', 'is_active', 'notes', 'created_by'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (self $exclusion): void {
            $exclusion->keyword = trim($exclusion->keyword);
            $exclusion->normalized_keyword = self::normalize($exclusion->keyword);
        });
    }

    public static function normalize(string $value): string
    {
        return Str::of($value)->lower()->squish()->toString();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFeatureAssignment extends Model
{
    protected $fillable = [
        'feature', 'primary_provider_id', 'primary_model', 'fallback_provider_id',
        'fallback_model', 'fallback_enabled', 'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return ['fallback_enabled' => 'boolean'];
    }

    public function primaryProvider(): BelongsTo { return $this->belongsTo(AiProvider::class, 'primary_provider_id'); }
    public function fallbackProvider(): BelongsTo { return $this->belongsTo(AiProvider::class, 'fallback_provider_id'); }
}

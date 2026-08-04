<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProvider extends Model
{
    protected $fillable = [
        'name', 'slug', 'driver', 'base_url', 'api_key', 'default_model',
        'connect_timeout_seconds', 'timeout_seconds', 'retry_times',
        'streaming_enabled', 'is_enabled', 'last_tested_at', 'last_test_status',
        'last_test_message', 'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'streaming_enabled' => 'boolean',
            'is_enabled' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function maskedKey(): ?string
    {
        $key = trim((string) $this->api_key);
        return $key === '' ? null : '••••••••'.substr($key, -4);
    }
}

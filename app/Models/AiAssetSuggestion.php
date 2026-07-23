<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAssetSuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id', 'requested_by', 'provider', 'model', 'status', 'source_type',
        'source_reference', 'suggestions', 'local_analysis', 'error_message',
        'input_tokens', 'output_tokens', 'total_tokens', 'estimated_cost_micros',
        'completed_at', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'suggestions' => 'array',
            'local_analysis' => 'array',
            'completed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}

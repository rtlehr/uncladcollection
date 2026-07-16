<?php

namespace App\Models;

use App\Enums\AssetFileRelationshipType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetFileRelationship extends Model
{
    protected $fillable = [
        'asset_id',
        'source_asset_file_id',
        'target_asset_file_id',
        'relationship_type',
        'label',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'relationship_type' => AssetFileRelationshipType::class,
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function sourceFile(): BelongsTo
    {
        return $this->belongsTo(
            AssetFile::class,
            'source_asset_file_id',
        )->withTrashed();
    }

    public function targetFile(): BelongsTo
    {
        return $this->belongsTo(
            AssetFile::class,
            'target_asset_file_id',
        )->withTrashed();
    }
}

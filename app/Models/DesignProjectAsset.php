<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignProjectAsset extends Model
{
    protected $fillable = ['design_project_id', 'license_id', 'asset_id', 'asset_file_id'];

    public function project(): BelongsTo { return $this->belongsTo(DesignProject::class, 'design_project_id'); }
    public function license(): BelongsTo { return $this->belongsTo(License::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function assetFile(): BelongsTo { return $this->belongsTo(AssetFile::class); }
}

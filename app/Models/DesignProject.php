<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DesignProject extends Model
{
    protected $fillable = ['uuid','user_id','license_id','asset_id','title','canvas_width','canvas_height','design_json','preview_path','status','last_opened_at'];
    protected $casts = ['design_json'=>'array','canvas_width'=>'integer','canvas_height'=>'integer','last_opened_at'=>'datetime'];

    protected static function booted(): void
    {
        static::creating(fn (DesignProject $project) => $project->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string { return 'uuid'; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function license(): BelongsTo { return $this->belongsTo(License::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function uploads(): HasMany { return $this->hasMany(DesignUpload::class); }
    public function exports(): HasMany { return $this->hasMany(DesignExport::class); }
}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
class DesignUpload extends Model {
 protected $fillable=['uuid','design_project_id','user_id','disk','path','original_filename','mime_type','size_bytes','width','height'];
 protected $casts=['size_bytes'=>'integer','width'=>'integer','height'=>'integer'];
 protected static function booted(): void { static::creating(fn(self $m)=>$m->uuid ??= (string) Str::uuid()); }
 public function project(): BelongsTo { return $this->belongsTo(DesignProject::class,'design_project_id'); }
}

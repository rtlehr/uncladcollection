<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
class DesignExport extends Model {
 protected $fillable=['uuid','design_project_id','user_id','width','height','format','fit_mode','status','disk','path','error_message','completed_at'];
 protected $casts=['width'=>'integer','height'=>'integer','completed_at'=>'datetime'];
 protected static function booted(): void { static::creating(fn(self $m)=>$m->uuid ??= (string) Str::uuid()); }
 public function project(): BelongsTo { return $this->belongsTo(DesignProject::class,'design_project_id'); }
}

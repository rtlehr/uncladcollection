<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
class DesignExport extends Model {
 protected $fillable=['uuid','request_token','design_project_id','user_id','studio_credit_transaction_id','studio_price_cents','studio_billing_type','studio_billing_snapshot','width','height','format','fit_mode','status','render_engine','queued_at','started_at','disk','path','original_filename','mime_type','size_bytes','preset_name','error_message','completed_at'];
 protected $casts=['studio_price_cents'=>'integer','studio_billing_snapshot'=>'array','width'=>'integer','height'=>'integer','size_bytes'=>'integer','queued_at'=>'datetime','started_at'=>'datetime','completed_at'=>'datetime'];
 protected static function booted(): void { static::creating(fn(self $m)=>$m->uuid ??= (string) Str::uuid()); }
 public function getRouteKeyName(): string { return 'uuid'; }
 public function project(): BelongsTo { return $this->belongsTo(DesignProject::class,'design_project_id'); }
 public function studioCreditTransaction(): BelongsTo { return $this->belongsTo(StudioCreditTransaction::class,'studio_credit_transaction_id'); }
}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
class AiGeneration extends Model
{
    protected $fillable=['uuid','feature','provider','model','status','input_text','input_context','output_text','output_data','prompt_example_ids','policy_keys','prompt_template_version','requested_by','accepted_at','rejected_at','error_message'];
    protected $casts=['input_context'=>'array','output_data'=>'array','prompt_example_ids'=>'array','policy_keys'=>'array','accepted_at'=>'datetime','rejected_at'=>'datetime'];
    protected static function booted(): void { static::creating(fn(self $m)=>$m->uuid ??= (string) Str::uuid()); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class,'requested_by'); }
}

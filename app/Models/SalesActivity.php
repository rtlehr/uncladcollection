<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SalesActivity extends Model{protected $fillable=['sponsorship_lead_id','user_id','type','subject','details','occurred_at','follow_up_at'];protected $casts=['occurred_at'=>'datetime','follow_up_at'=>'date'];public function lead():BelongsTo{return $this->belongsTo(SponsorshipLead::class,'sponsorship_lead_id');}public function user():BelongsTo{return $this->belongsTo(User::class);}}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class SponsorshipPackage extends Model{use HasFactory;protected $fillable=['uuid','name','code','description','duration_days','base_price_cents','package_price_cents','impression_goal','click_goal','included_creatives','billing_terms','is_active','internal_notes'];protected $casts=['is_active'=>'boolean','base_price_cents'=>'integer','package_price_cents'=>'integer'];public function placements():BelongsToMany{return $this->belongsToMany(AdPlacement::class,'sponsorship_package_placement')->withPivot(['quantity','priority']);}}

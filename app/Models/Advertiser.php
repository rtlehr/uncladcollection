<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes; use Illuminate\Database\Eloquent\Relations\HasMany;
class Advertiser extends Model { use HasFactory, SoftDeletes; protected $fillable=['uuid','name','slug','status','website_url','billing_email','contact_name','contact_email','contact_phone','billing_address','notes']; public function campaigns(): HasMany{return $this->hasMany(AdvertisingCampaign::class);} }

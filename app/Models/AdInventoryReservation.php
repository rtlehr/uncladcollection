<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AdInventoryReservation extends Model{protected $fillable=['uuid','ad_placement_id','advertising_campaign_id','sponsorship_proposal_id','status','starts_on','ends_on','hold_expires_at','quantity','notes'];protected $casts=['starts_on'=>'date','ends_on'=>'date','hold_expires_at'=>'datetime'];public function placement():BelongsTo{return $this->belongsTo(AdPlacement::class,'ad_placement_id');}public function campaign():BelongsTo{return $this->belongsTo(AdvertisingCampaign::class);}public function proposal():BelongsTo{return $this->belongsTo(SponsorshipProposal::class);}}

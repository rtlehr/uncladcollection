<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SponsorshipProposalItem extends Model{protected $fillable=['sponsorship_proposal_id','ad_placement_id','description','billing_model','quantity','unit_amount_cents','line_total_cents','metadata'];protected $casts=['metadata'=>'array'];public function proposal():BelongsTo{return $this->belongsTo(SponsorshipProposal::class);}public function placement():BelongsTo{return $this->belongsTo(AdPlacement::class,'ad_placement_id');}}

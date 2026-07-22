<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SponsorshipProposalStatusHistory extends Model
{
    protected $fillable = [
        'sponsorship_proposal_id', 'user_id', 'from_status', 'to_status', 'reason',
        'source', 'ip_address', 'user_agent',
    ];

    public function proposal(): BelongsTo { return $this->belongsTo(SponsorshipProposal::class, 'sponsorship_proposal_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}

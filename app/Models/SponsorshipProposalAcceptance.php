<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SponsorshipProposalAcceptance extends Model
{
    protected $fillable = [
        'sponsorship_proposal_id', 'user_id', 'signer_name', 'signer_title',
        'signer_email', 'signer_company', 'terms_acknowledged', 'accepted_at',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'terms_acknowledged' => 'boolean',
        'accepted_at' => 'datetime',
    ];

    public function proposal(): BelongsTo { return $this->belongsTo(SponsorshipProposal::class, 'sponsorship_proposal_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}

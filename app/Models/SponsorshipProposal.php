<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};

class SponsorshipProposal extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'sent', 'accepted', 'declined', 'expired', 'converted'];

    protected $fillable = [
        'uuid', 'proposal_number', 'sponsorship_lead_id', 'advertiser_id',
        'sponsorship_package_id', 'created_by', 'title', 'status', 'starts_on',
        'ends_on', 'expires_on', 'currency', 'subtotal_cents', 'discount_cents',
        'tax_cents', 'total_cents', 'terms', 'notes', 'sent_at', 'accepted_at',
        'declined_at', 'converted_campaign_id', 'converted_invoice_id', 'converted_at',
    ];

    protected $casts = [
        'starts_on' => 'date', 'ends_on' => 'date', 'expires_on' => 'date',
        'sent_at' => 'datetime', 'accepted_at' => 'datetime',
        'declined_at' => 'datetime', 'converted_at' => 'datetime',
    ];

    public function lead(): BelongsTo { return $this->belongsTo(SponsorshipLead::class, 'sponsorship_lead_id'); }
    public function advertiser(): BelongsTo { return $this->belongsTo(Advertiser::class); }
    public function package(): BelongsTo { return $this->belongsTo(SponsorshipPackage::class, 'sponsorship_package_id'); }
    public function items(): HasMany { return $this->hasMany(SponsorshipProposalItem::class); }
    public function campaign(): BelongsTo { return $this->belongsTo(AdvertisingCampaign::class, 'converted_campaign_id'); }
    public function invoice(): BelongsTo { return $this->belongsTo(AdvertisingInvoice::class, 'converted_invoice_id'); }
    public function acceptance(): HasOne { return $this->hasOne(SponsorshipProposalAcceptance::class); }
    public function statusHistory(): HasMany { return $this->hasMany(SponsorshipProposalStatusHistory::class)->latest(); }

    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->isBefore(today());
    }

    public function canBeRespondedTo(): bool
    {
        return $this->status === 'sent' && ! $this->isExpired() && $this->acceptance === null;
    }
}

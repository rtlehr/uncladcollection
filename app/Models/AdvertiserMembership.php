<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvertiserMembership extends Model
{
    use HasFactory;

    public const ROLES = ['owner', 'billing_contact', 'campaign_manager', 'creative_contributor', 'report_viewer'];

    protected $fillable = [
        'advertiser_id', 'user_id', 'role', 'is_primary', 'is_active',
        'invited_at', 'accepted_at', 'invited_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean', 'is_active' => 'boolean',
        'invited_at' => 'datetime', 'accepted_at' => 'datetime',
    ];

    public function advertiser(): BelongsTo { return $this->belongsTo(Advertiser::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function inviter(): BelongsTo { return $this->belongsTo(User::class, 'invited_by'); }

    public function canManageCampaigns(): bool { return in_array($this->role, ['owner', 'campaign_manager'], true); }
    public function canManageCreatives(): bool { return in_array($this->role, ['owner', 'campaign_manager', 'creative_contributor'], true); }
    public function canViewBilling(): bool { return in_array($this->role, ['owner', 'billing_contact'], true); }
    public function canManageAccount(): bool { return $this->role === 'owner'; }
}

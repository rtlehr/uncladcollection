<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseStatusHistory extends Model
{
    protected $fillable = [
        'license_id', 'changed_by_user_id', 'from_status', 'to_status',
        'reason', 'customer_message', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function license(): BelongsTo { return $this->belongsTo(License::class); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by_user_id'); }
}

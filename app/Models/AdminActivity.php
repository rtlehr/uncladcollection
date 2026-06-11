<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminActivity extends Model
{
    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'subject_name',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
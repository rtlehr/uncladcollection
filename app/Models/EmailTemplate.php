<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key', 'name', 'category', 'description', 'subject', 'preview_text',
        'body_html', 'body_text', 'variables', 'required_variables',
        'is_transactional', 'is_active', 'is_system', 'updated_by_user_id',
    ];

    protected $casts = [
        'variables' => 'array',
        'required_variables' => 'array',
        'is_transactional' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function revisions(): HasMany
    {
        return $this->hasMany(EmailTemplateRevision::class)->latest('revision_number');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}

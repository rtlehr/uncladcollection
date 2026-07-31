<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplateRevision extends Model
{
    protected $fillable = [
        'email_template_id', 'revision_number', 'subject', 'preview_text',
        'body_html', 'body_text', 'is_active', 'created_by_user_id',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

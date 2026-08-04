<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSavedPromptVersion extends Model
{
    protected $fillable = [
        'ai_saved_prompt_id','version_number','prompt_text','refinement_instruction','provider','model','created_by',
    ];

    public function prompt(): BelongsTo { return $this->belongsTo(AiSavedPrompt::class, 'ai_saved_prompt_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}

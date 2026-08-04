<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AiSavedPrompt extends Model
{
    protected $fillable = [
        'uuid','title','description','prompt_text','intended_use','content_context','output_mode',
        'body_detail_level','description_depth','character_detail_level','environment_detail_level',
        'describe_every_visible_person','orientation','additional_instructions','provider','model',
        'source_generation_id','created_by','updated_by',
    ];

    protected $casts = ['describe_every_visible_person' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(fn (self $prompt) => $prompt->uuid ??= (string) Str::uuid());
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AiSavedPromptVersion::class)->orderByDesc('version_number');
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function sourceGeneration(): BelongsTo { return $this->belongsTo(AiGeneration::class, 'source_generation_id'); }
}

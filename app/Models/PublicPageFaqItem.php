<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicPageFaqItem extends Model
{
    use HasFactory;

    protected $fillable = ['public_page_id', 'question', 'answer', 'is_active', 'sort_order'];
    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(PublicPage::class, 'public_page_id');
    }
}

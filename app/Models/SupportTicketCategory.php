<?php

namespace App\Models;

use App\Enums\SupportTicketPriority;
use Database\Factories\SupportTicketCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicketCategory extends Model
{
    /** @use HasFactory<SupportTicketCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'default_priority', 'default_assignee_id',
        'is_public', 'is_member', 'is_advertiser', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'default_priority' => SupportTicketPriority::class,
            'is_public' => 'boolean',
            'is_member' => 'boolean',
            'is_advertiser' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'category_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CommentLike;
use App\Models\CommentReport;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_HIDDEN = 'hidden';
    public const STATUS_SPAM = 'spam';
    public const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
        'parent_id',
        'body',
        'status',
        'depth',
        'likes_count',
        'reports_count',
        'is_pinned',
        'is_edited',
        'edited_at',
        'hidden_at',
        'approved_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'hidden_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->with(['user', 'replies'])
            ->orderBy('created_at');
    }

    public function approvedReplies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->where('status', self::STATUS_APPROVED)
            ->with(['user', 'approvedReplies'])
            ->orderBy('created_at');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopePinnedFirst($query)
    {
        return $query->orderByDesc('is_pinned');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CommentReport::class);
    }

}
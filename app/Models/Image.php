<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_id',
        'title',
        'slug',
        'description',
        'original_path',
        'high_res_path',
        'thumbnail_path',
        'icon_path',
        'photographer',
        'sort_order',
        'is_active',
        'downloads_count',
        'favorites_count',
        'purchases_count',
        'views_count',
        'is_ai_generated',
    ];

    protected $appends = [
        'original_url',
        'high_res_url',
        'thumbnail_url',
        'icon_url',
    ];

    protected function casts(): array
    {
        return [
            'collection_id' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_ai_generated' => 'boolean',
            'downloads_count' => 'integer',
            'favorites_count' => 'integer',
            'purchases_count' => 'integer',
            'views_count' => 'integer',
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function asset(): HasOne
    {
        return $this->hasOne(Asset::class, 'legacy_image_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)
            ->withTimestamps();
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(ImageFavorite::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function getActivityName(): string
    {
        return $this->title;
    }

    public function getOriginalUrlAttribute(): ?string
    {
        return $this->original_path
            ? Storage::url($this->original_path)
            : null;
    }

    public function getHighResUrlAttribute(): ?string
    {
        return $this->high_res_path
            ? Storage::url($this->high_res_path)
            : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path
            ? Storage::url($this->thumbnail_path)
            : null;
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->icon_path
            ? Storage::url($this->icon_path)
            : null;
    }
}

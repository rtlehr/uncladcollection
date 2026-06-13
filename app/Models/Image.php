<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected $casts = [
        'collection_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
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

    public function getActivityName(): string
    {
        return $this->title;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_ai_generated' => 'boolean',
        ];
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

}
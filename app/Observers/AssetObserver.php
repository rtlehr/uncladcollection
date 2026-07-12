<?php

namespace App\Observers;

use App\Models\Asset;
use Illuminate\Support\Str;

class AssetObserver
{
    public function creating(Asset $asset): void
    {
        $asset->uuid ??= (string) Str::uuid();

        if (! $asset->slug) {
            $asset->slug = $this->uniqueSlug($asset->title ?: 'asset');
        }
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'asset';
        $slug = $base;
        $suffix = 2;

        while (Asset::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}

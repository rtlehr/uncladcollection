<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AssetTagService
{
    public function syncNames(Asset $asset, array $names): Collection
    {
        $normalized = collect($names)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn (string $name) => Str::lower($name))
            ->take(50)
            ->values();

        $tags = $normalized->map(function (string $name): Tag {
            $slug = Str::slug($name);

            return Tag::query()->firstOrCreate(
                ['slug' => $slug, 'tag_type' => 'image'],
                ['name' => $name, 'description' => null],
            );
        });

        $asset->tags()->sync($tags->pluck('id')->all());
        $asset->forceFill(['keywords' => $tags->pluck('name')->values()->all()])->saveQuietly();

        return $tags;
    }

    public function mergeNames(Asset $asset, array $names): Collection
    {
        return $this->syncNames(
            $asset,
            $asset->tags()->pluck('name')->merge($names)->all(),
        );
    }
}

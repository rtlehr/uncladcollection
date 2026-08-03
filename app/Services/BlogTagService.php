<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BlogTagService
{
    /** @return Collection<int, Tag> */
    public function resolveNames(array $names): Collection
    {
        $normalized = collect($names)
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn (string $name) => $name !== '' && Str::slug($name) !== '')
            ->unique(fn (string $name) => Str::lower($name))
            ->take(50)
            ->values();

        return $normalized->map(function (string $name): Tag {
            return Tag::query()->firstOrCreate(
                ['slug' => Str::slug($name), 'tag_type' => 'blog'],
                ['name' => $name, 'description' => null],
            );
        });
    }

    /** @return Collection<int, Tag> */
    public function syncNames(BlogPost $blogPost, array $names): Collection
    {
        $tags = $this->resolveNames($names);
        $blogPost->tags()->sync($tags->pluck('id')->all());

        return $tags;
    }

    /** @return Collection<int, Tag> */
    public function mergeNames(BlogPost $blogPost, array $names): Collection
    {
        return $this->syncNames(
            $blogPost,
            $blogPost->tags()->pluck('name')->merge($names)->all(),
        );
    }
}

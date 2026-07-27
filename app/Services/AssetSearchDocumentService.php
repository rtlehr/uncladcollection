<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetSearchDocument;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AssetSearchDocumentService
{
    public function rebuild(Asset $asset): void
    {
        if (! Schema::hasTable('asset_search_documents')) {
            return;
        }

        $asset->loadMissing(['collection:id,name', 'categories:id,name', 'tags:id,name', 'activeFiles']);
        $dimensions = $this->dimensions($asset);

        AssetSearchDocument::query()->updateOrCreate(
            ['asset_id' => $asset->id],
            [
                'normalized_title' => $this->normalize($asset->title),
                'search_text' => $this->searchText($asset),
                'orientation' => $dimensions['orientation'],
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'indexed_at' => now(),
            ],
        );
    }

    public function delete(Asset $asset): void
    {
        if (Schema::hasTable('asset_search_documents')) {
            AssetSearchDocument::query()->whereKey($asset->id)->delete();
        }
    }

    private function searchText(Asset $asset): string
    {
        $metadata = Arr::flatten($asset->metadata ?? []);
        $values = [
            $asset->title, $asset->description, $asset->alt_text, $asset->seo_title,
            $asset->seo_description, $asset->photographer, $asset->collection?->name,
            ...($asset->keywords ?? []), ...($asset->detected_objects ?? []),
            ...$asset->categories->pluck('name')->all(), ...$asset->tags->pluck('name')->all(),
            ...$asset->activeFiles->pluck('original_filename')->all(),
            ...$asset->activeFiles->pluck('extension')->all(), ...$metadata,
        ];

        return $this->normalize(implode(' ', array_filter($values, fn ($value) => is_scalar($value))));
    }

    private function normalize(?string $value): string
    {
        return Str::of((string) $value)->lower()->replaceMatches('/[^\pL\pN]+/u', ' ')->squish()->toString();
    }

    /** @return array{width:?int,height:?int,orientation:?string} */
    private function dimensions(Asset $asset): array
    {
        $file = $asset->activeFiles
            ->filter(fn ($file) => $file->width && $file->height)
            ->sortByDesc(fn ($file) => ((int) $file->width) * ((int) $file->height))
            ->first();

        if (! $file) return ['width' => null, 'height' => null, 'orientation' => null];
        $width = (int) $file->width; $height = (int) $file->height;
        $ratio = $height > 0 ? $width / $height : 1;
        $orientation = $ratio > 1.08 ? 'landscape' : ($ratio < .92 ? 'portrait' : 'square');
        return compact('width', 'height', 'orientation');
    }
}

<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AssetAiSourceService
{
    public function resolve(Asset $asset): array
    {
        $presentation = $asset->presentation_images ?? [];
        $path = data_get($presentation, 'marketplace.path');
        $disk = data_get($presentation, 'marketplace.disk', 'public');

        if (is_string($path) && $path !== '' && Storage::disk($disk)->exists($path)) {
            return ['path' => Storage::disk($disk)->path($path), 'type' => 'marketplace_image', 'reference' => $path];
        }

        $file = $asset->primaryPreviewFile ?: $asset->activeFiles()->where('mime_type', 'like', 'image/%')->first();
        if ($file && $file->exists()) {
            return ['path' => Storage::disk($file->disk)->path($file->path), 'type' => 'asset_file', 'reference' => (string) $file->id];
        }

        throw new RuntimeException('Add a marketplace image or image preview before requesting AI suggestions.');
    }
}

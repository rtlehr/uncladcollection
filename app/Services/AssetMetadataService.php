<?php

namespace App\Services;

use App\Enums\AssetMediaType;
use App\Models\AssetFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AssetMetadataService
{
    public function extract(AssetFile $assetFile): array
    {
        $metadata = [
            'detected_at' => now()->toIso8601String(),
        ];

        if ($assetFile->media_type !== AssetMediaType::Image) {
            return $metadata;
        }

        [$localPath, $temporary] = $this->localPath($assetFile);

        try {
            $dimensions = @getimagesize($localPath);

            if ($dimensions === false) {
                throw new RuntimeException('Image dimensions could not be read.');
            }

            return $metadata + [
                'width' => (int) $dimensions[0],
                'height' => (int) $dimensions[1],
                'image_type' => $dimensions[2] ?? null,
                'bits' => $dimensions['bits'] ?? null,
                'channels' => $dimensions['channels'] ?? null,
            ];
        } finally {
            if ($temporary && is_file($localPath)) {
                @unlink($localPath);
            }
        }
    }

    private function localPath(AssetFile $assetFile): array
    {
        $disk = Storage::disk($assetFile->disk);

        try {
            $path = $disk->path($assetFile->path);

            if (is_file($path)) {
                return [$path, false];
            }
        } catch (\Throwable) {
            // Remote disks may not provide a local path.
        }

        $stream = $disk->readStream($assetFile->path);

        if (! is_resource($stream)) {
            throw new RuntimeException('The asset file could not be opened for metadata extraction.');
        }

        $temporary = tempnam(sys_get_temp_dir(), 'asset-meta-');

        if ($temporary === false) {
            fclose($stream);
            throw new RuntimeException('A temporary metadata file could not be created.');
        }

        $target = fopen($temporary, 'wb');

        if (! is_resource($target)) {
            fclose($stream);
            @unlink($temporary);
            throw new RuntimeException('The temporary metadata file could not be opened.');
        }

        stream_copy_to_stream($stream, $target);
        fclose($stream);
        fclose($target);

        return [$temporary, true];
    }
}

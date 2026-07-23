<?php

namespace App\Services;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;

class AssetAiPreviewService
{
    public function create(string $sourcePath): string
    {
        $dimension = max(256, (int) config('ai-assets.max_image_dimension', 1600));
        $quality = min(95, max(50, (int) config('ai-assets.jpeg_quality', 82)));
        $temporary = tempnam(sys_get_temp_dir(), 'uc-ai-preview-');

        if ($temporary === false) {
            throw new RuntimeException('A temporary AI preview file could not be created.');
        }

        $target = $temporary.'.jpg';
        @unlink($temporary);

        try {
            (new ImageManager(new Driver()))
                ->decode($sourcePath)
                ->scaleDown(width: $dimension, height: $dimension)
                ->save($target, quality: $quality);
        } catch (\Throwable $exception) {
            @unlink($target);
            throw new RuntimeException('The reduced AI preview could not be created.', previous: $exception);
        }

        return $target;
    }

    public function delete(?string $path): void
    {
        if (is_string($path) && $path !== '' && is_file($path)) {
            @unlink($path);
        }
    }
}

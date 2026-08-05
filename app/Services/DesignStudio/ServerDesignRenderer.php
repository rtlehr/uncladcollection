<?php

namespace App\Services\DesignStudio;

use App\Enums\AssetFileRole;
use App\Models\AssetFile;
use App\Models\DesignExport;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ServerDesignRenderer
{
    public function render(DesignExport $export, string $overlayPath): void
    {
        abort_unless(extension_loaded('gd'), 500, 'The GD image extension is required for server rendering.');

        $project = $export->project;
        if (! $project) {
            throw new RuntimeException('The design project could not be loaded.');
        }
        if ($project->license_id !== null && ! $project->license?->isActive()) {
            throw new RuntimeException('The design license is no longer active.');
        }

        $renderWidth = (int) $export->width;
        $renderHeight = (int) $export->height;
        $pixelCount = $renderWidth * $renderHeight;
        if ($pixelCount > (int) config('design-studio.max_server_pixels', 80000000)) {
            throw new RuntimeException('The requested export is larger than the configured server-rendering limit.');
        }

        $canvas = imagecreatetruecolor($renderWidth, $renderHeight);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        if ($project->license_id !== null) {
            $sourceFile = $this->sourceFile($project->asset?->activeFiles ?? collect());
            $sourceBytes = Storage::disk($sourceFile->disk)->get($sourceFile->path);
            $source = @imagecreatefromstring($sourceBytes);
            if (! $source) {
                throw new RuntimeException('The licensed source image could not be decoded.');
            }

            if ($export->fit_mode === 'contain') {
                $this->drawContained($canvas, $source, 0, 0, $renderWidth, $renderHeight);
            } else {
                $this->drawCovered($canvas, $source, 0, 0, $renderWidth, $renderHeight);
            }
            imagedestroy($source);
        }

        if (! Storage::disk('local')->exists($overlayPath)) {
            throw new RuntimeException('The pixel-perfect Fabric overlay is missing.');
        }

        $overlay = @imagecreatefromstring(Storage::disk('local')->get($overlayPath));
        if (! $overlay) {
            throw new RuntimeException('The pixel-perfect Fabric overlay could not be decoded.');
        }
        if (imagesx($overlay) !== $renderWidth || imagesy($overlay) !== $renderHeight) {
            imagedestroy($overlay);
            throw new RuntimeException('The Fabric overlay dimensions do not match the export dimensions.');
        }

        imagealphablending($canvas, true);
        imagecopy($canvas, $overlay, 0, 0, 0, 0, $renderWidth, $renderHeight);
        imagedestroy($overlay);
        Storage::disk('local')->delete($overlayPath);

        $format = strtolower($export->format) === 'jpeg' ? 'jpg' : strtolower($export->format);
        $filename = sprintf('%s-%dx%d-server-%s.%s', $project->uuid, $renderWidth, $renderHeight, now()->format('Ymd-His'), $format);
        $path = "designs/{$export->user_id}/{$project->uuid}/exports/{$filename}";

        ob_start();
        if ($format === 'png') {
            imagepng($canvas, null, 6);
            $mime = 'image/png';
        } elseif ($format === 'webp' && function_exists('imagewebp')) {
            imagewebp($canvas, null, (int) config('design-studio.webp_quality', 90));
            $mime = 'image/webp';
        } else {
            imagejpeg($canvas, null, (int) config('design-studio.jpeg_quality', 92));
            $format = 'jpg';
            $mime = 'image/jpeg';
        }
        $contents = (string) ob_get_clean();
        imagedestroy($canvas);

        Storage::disk('local')->put($path, $contents);
        $export->forceFill([
            'format' => $format,
            'status' => 'completed',
            'render_engine' => 'server-gd',
            'disk' => 'local',
            'path' => $path,
            'original_filename' => $filename,
            'mime_type' => $mime,
            'size_bytes' => strlen($contents),
            'completed_at' => now(),
            'error_message' => null,
        ])->save();
    }

    private function sourceFile($files): AssetFile
    {
        $priority = [
            AssetFileRole::Source->value => 0,
            AssetFileRole::Print->value => 1,
            AssetFileRole::HighResolution->value => 2,
            AssetFileRole::Primary->value => 3,
            AssetFileRole::Preview->value => 4,
        ];

        $file = $files
            ->filter(fn (AssetFile $file) => str_starts_with((string) $file->mime_type, 'image/') && $file->exists())
            ->sortBy(fn (AssetFile $file) => [
                $priority[$file->role?->value ?? (string) $file->role] ?? 9,
                -((int) $file->width * (int) $file->height),
            ])
            ->first();

        if (! $file) {
            throw new RuntimeException('No renderable licensed source image is available.');
        }

        return $file;
    }

    private function drawCovered($destination, $source, int $x, int $y, int $width, int $height): void
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = max($width / $sourceWidth, $height / $sourceHeight);
        $cropWidth = (int) round($width / $scale);
        $cropHeight = (int) round($height / $scale);
        $sourceX = (int) max(0, ($sourceWidth - $cropWidth) / 2);
        $sourceY = (int) max(0, ($sourceHeight - $cropHeight) / 2);
        imagecopyresampled($destination, $source, $x, $y, $sourceX, $sourceY, $width, $height, $cropWidth, $cropHeight);
    }

    private function drawContained($destination, $source, int $x, int $y, int $width, int $height): void
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min($width / $sourceWidth, $height / $sourceHeight);
        $drawWidth = max(1, (int) round($sourceWidth * $scale));
        $drawHeight = max(1, (int) round($sourceHeight * $scale));
        $drawX = $x + (int) round(($width - $drawWidth) / 2);
        $drawY = $y + (int) round(($height - $drawHeight) / 2);
        imagecopyresampled($destination, $source, $drawX, $drawY, 0, 0, $drawWidth, $drawHeight, $sourceWidth, $sourceHeight);
    }
}

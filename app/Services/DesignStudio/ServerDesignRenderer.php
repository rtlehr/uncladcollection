<?php

namespace App\Services\DesignStudio;

use App\Enums\AssetFileRole;
use App\Models\AssetFile;
use App\Models\DesignExport;
use App\Models\DesignUpload;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ServerDesignRenderer
{
    public function render(DesignExport $export, string $overlayPath): void
    {
        abort_unless(extension_loaded('gd'), 500, 'The GD image extension is required for server rendering.');

        $project = $export->project;
        if (! $project || ! $project->license?->isActive()) {
            throw new RuntimeException('The design license is no longer active.');
        }

        $sourceFile = $this->sourceFile($project->asset?->activeFiles ?? collect());
        $sourceBytes = Storage::disk($sourceFile->disk)->get($sourceFile->path);
        $source = @imagecreatefromstring($sourceBytes);
        if (! $source) {
            throw new RuntimeException('The licensed source image could not be decoded.');
        }

        $designWidth = max(1, (int) $project->canvas_width);
        $designHeight = max(1, (int) $project->canvas_height);
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

        $this->drawCovered($canvas, $source, 0, 0, $renderWidth, $renderHeight);
        imagedestroy($source);

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

    private function drawRect($canvas, array $object, int $designWidth, int $designHeight, int $renderWidth, int $renderHeight): void
    {
        [$layer, $left, $top] = $this->layer($object, $designWidth, $designHeight, $renderWidth, $renderHeight);
        $width = max(1, (int) round((float) ($object['width'] ?? 1) * abs((float) ($object['scaleX'] ?? 1)) * $renderWidth / $designWidth));
        $height = max(1, (int) round((float) ($object['height'] ?? 1) * abs((float) ($object['scaleY'] ?? 1)) * $renderHeight / $designHeight));
        $fill = $this->color($layer, (string) ($object['fill'] ?? '#000000'), (float) ($object['opacity'] ?? 1));
        imagefilledrectangle($layer, 0, 0, $width - 1, $height - 1, $fill);
        $strokeWidth = max(0, (int) round((float) ($object['strokeWidth'] ?? 0) * (($renderWidth / $designWidth + $renderHeight / $designHeight) / 2)));
        if ($strokeWidth > 0) {
            $stroke = $this->color($layer, (string) ($object['stroke'] ?? '#ffffff'), (float) ($object['opacity'] ?? 1));
            imagesetthickness($layer, $strokeWidth);
            imagerectangle($layer, 0, 0, $width - 1, $height - 1, $stroke);
        }
        $this->placeRotated($canvas, $layer, $left, $top, (float) ($object['angle'] ?? 0));
    }

    private function drawUpload($canvas, array $object, $uploads, int $designWidth, int $designHeight, int $renderWidth, int $renderHeight): void
    {
        $uuid = (string) ($object['uploadUuid'] ?? '');
        /** @var DesignUpload|null $upload */
        $upload = $uploads->firstWhere('uuid', $uuid);
        if (! $upload || ! Storage::disk($upload->disk)->exists($upload->path)) {
            return;
        }
        $image = @imagecreatefromstring(Storage::disk($upload->disk)->get($upload->path));
        if (! $image) return;
        $width = max(1, (int) round((float) ($object['width'] ?? imagesx($image)) * abs((float) ($object['scaleX'] ?? 1)) * $renderWidth / $designWidth));
        $height = max(1, (int) round((float) ($object['height'] ?? imagesy($image)) * abs((float) ($object['scaleY'] ?? 1)) * $renderHeight / $designHeight));
        $layer = $this->transparent($width, $height);
        imagecopyresampled($layer, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
        imagedestroy($image);
        $left = (int) round((float) ($object['left'] ?? 0) * $renderWidth / $designWidth);
        $top = (int) round((float) ($object['top'] ?? 0) * $renderHeight / $designHeight);
        $this->placeRotated($canvas, $layer, $left, $top, (float) ($object['angle'] ?? 0));
    }

    private function drawText($canvas, array $object, int $designWidth, int $designHeight, int $renderWidth, int $renderHeight): void
    {
        $text = (string) ($object['text'] ?? '');
        if ($text === '') return;
        $scale = (($renderWidth / $designWidth) + ($renderHeight / $designHeight)) / 2;
        $fontSize = max(8, (int) round((float) ($object['fontSize'] ?? 48) * abs((float) ($object['scaleY'] ?? 1)) * $scale));
        $font = $this->fontPath();
        $lines = preg_split('/\R/u', $text) ?: [$text];
        $lineHeight = (int) round($fontSize * (float) ($object['lineHeight'] ?? 1.16));
        $width = max(10, (int) round((float) ($object['width'] ?? 400) * abs((float) ($object['scaleX'] ?? 1)) * $renderWidth / $designWidth));
        $height = max($lineHeight, count($lines) * $lineHeight + $fontSize);
        $layer = $this->transparent($width + $fontSize, $height + $fontSize);
        $opacity = (float) ($object['opacity'] ?? 1);
        $fill = $this->color($layer, (string) ($object['fill'] ?? '#ffffff'), $opacity);
        $background = (string) ($object['textBackgroundColor'] ?? '');
        if ($background !== '') {
            imagefilledrectangle($layer, 0, 0, imagesx($layer) - 1, imagesy($layer) - 1, $this->color($layer, $background, $opacity));
        }
        $strokeWidth = max(0, (int) round((float) ($object['strokeWidth'] ?? 0) * $scale));
        $stroke = $this->color($layer, (string) ($object['stroke'] ?? '#000000'), $opacity);
        foreach ($lines as $index => $line) {
            $y = $fontSize + ($index * $lineHeight);
            $bbox = imagettfbbox($fontSize, 0, $font, $line) ?: [0, 0, 0, 0, 0, 0, 0, 0];
            $textWidth = abs((int) $bbox[2] - (int) $bbox[0]);
            $align = (string) ($object['textAlign'] ?? 'left');
            $x = $align === 'center' ? max(0, (int) (($width - $textWidth) / 2)) : ($align === 'right' ? max(0, $width - $textWidth) : 0);
            if (! empty($object['shadow'])) {
                imagettftext($layer, $fontSize, 0, $x + 4, $y + 6, $this->color($layer, '#000000', .45 * $opacity), $font, $line);
            }
            if ($strokeWidth > 0) {
                for ($dx = -$strokeWidth; $dx <= $strokeWidth; $dx++) for ($dy = -$strokeWidth; $dy <= $strokeWidth; $dy++) if ($dx || $dy) imagettftext($layer, $fontSize, 0, $x + $dx, $y + $dy, $stroke, $font, $line);
            }
            imagettftext($layer, $fontSize, 0, $x, $y, $fill, $font, $line);
        }
        $left = (int) round((float) ($object['left'] ?? 0) * $renderWidth / $designWidth);
        $top = (int) round((float) ($object['top'] ?? 0) * $renderHeight / $designHeight);
        $this->placeRotated($canvas, $layer, $left, $top, (float) ($object['angle'] ?? 0));
    }

    private function layer(array $object, int $designWidth, int $designHeight, int $renderWidth, int $renderHeight): array
    {
        $width = max(1, (int) round((float) ($object['width'] ?? 1) * abs((float) ($object['scaleX'] ?? 1)) * $renderWidth / $designWidth));
        $height = max(1, (int) round((float) ($object['height'] ?? 1) * abs((float) ($object['scaleY'] ?? 1)) * $renderHeight / $designHeight));
        return [$this->transparent($width, $height), (int) round((float) ($object['left'] ?? 0) * $renderWidth / $designWidth), (int) round((float) ($object['top'] ?? 0) * $renderHeight / $designHeight)];
    }

    private function transparent(int $width, int $height)
    {
        $image = imagecreatetruecolor(max(1, $width), max(1, $height));
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagealphablending($image, true);
        return $image;
    }

    private function placeRotated($canvas, $layer, int $left, int $top, float $angle): void
    {
        if (abs($angle) > .01) {
            $transparent = imagecolorallocatealpha($layer, 0, 0, 0, 127);
            $rotated = imagerotate($layer, -$angle, $transparent);
            imagedestroy($layer);
            $layer = $rotated;
        }
        imagecopy($canvas, $layer, $left, $top, 0, 0, imagesx($layer), imagesy($layer));
        imagedestroy($layer);
    }

    private function color($image, string $value, float $opacity = 1): int
    {
        $r = $g = $b = 0; $alpha = 0;
        if (preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/i', $value, $m)) {
            [$r, $g, $b] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            $opacity *= isset($m[4]) ? (float) $m[4] : 1;
        } elseif (preg_match('/^#([0-9a-f]{6})$/i', $value, $m)) {
            $r = hexdec(substr($m[1], 0, 2)); $g = hexdec(substr($m[1], 2, 2)); $b = hexdec(substr($m[1], 4, 2));
        }
        $alpha = 127 - (int) round(max(0, min(1, $opacity)) * 127);
        return imagecolorallocatealpha($image, $r, $g, $b, $alpha);
    }

    private function fontPath(): string
    {
        $candidates = array_filter([
            config('design-studio.font_path'),
            'C:\\Windows\\Fonts\\arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans.ttf',
        ]);
        foreach ($candidates as $candidate) if (is_file($candidate)) return $candidate;
        throw new RuntimeException('No TrueType font is configured for server-side text rendering. Set DESIGN_STUDIO_FONT_PATH.');
    }
}

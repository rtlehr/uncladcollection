<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Alignment;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetWatermarkPreviewService
{
    private const OUTPUT_DISK = 'public';
    private const MAX_WIDTH = 1800;
    private const MAX_HEIGHT = 1800;

    private ImageManager $manager;

    public function __construct(
        private readonly SiteSettingService $settings,
    ) {
        $this->manager = new ImageManager(new Driver());
    }

    public function supports(AssetFile $file): bool
    {
        return in_array(strtolower($file->extension), ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    public function assetFileResponse(Asset $asset, AssetFile $file): BinaryFileResponse
    {
        abort_unless($file->asset_id === $asset->id && $file->is_active, 404);
        abort_unless($this->supports($file), 415);

        $preview = $this->ensurePreview(
            asset: $asset,
            sourceDisk: $file->disk,
            sourcePath: $file->path,
            identity: 'file-'.$file->uuid,
            sourceFingerprint: $file->checksum_sha256 ?: $this->storageFingerprint($file->disk, $file->path),
        );

        return $this->response($preview['path']);
    }

    public function marketplaceResponse(Asset $asset): BinaryFileResponse
    {
        $sourceDisk = (string) data_get($asset->presentation_images, 'marketplace.disk', 'public');
        $sourcePath = data_get($asset->presentation_images, 'marketplace.path');

        abort_unless(is_string($sourcePath) && $sourcePath !== '', 404);
        abort_unless(Storage::disk($sourceDisk)->exists($sourcePath), 404);

        $preview = $this->ensurePreview(
            asset: $asset,
            sourceDisk: $sourceDisk,
            sourcePath: $sourcePath,
            identity: 'marketplace',
            sourceFingerprint: $this->storageFingerprint($sourceDisk, $sourcePath),
        );

        return $this->response($preview['path']);
    }

    public function marketplaceRoute(Asset $asset): ?string
    {
        $sourcePath = data_get($asset->presentation_images, 'marketplace.path');

        if (! is_string($sourcePath) || $sourcePath === '') {
            return null;
        }

        return route('assets.marketplace-preview', ['asset' => $asset, 'v' => $this->version()]);
    }

    public function version(): string
    {
        return substr(hash('sha256', json_encode($this->configuration(), JSON_THROW_ON_ERROR)), 0, 12);
    }

    /**
     * @return array{path:string,fingerprint:string,watermarked:bool}
     */
    private function ensurePreview(
        Asset $asset,
        string $sourceDisk,
        string $sourcePath,
        string $identity,
        string $sourceFingerprint,
    ): array {
        abort_unless(Storage::disk($sourceDisk)->exists($sourcePath), 404);

        $configuration = $this->configuration();
        $fingerprint = hash('sha256', json_encode([
            'source' => $sourceFingerprint,
            'configuration' => $configuration,
            'version' => 1,
        ], JSON_THROW_ON_ERROR));

        $directory = "assets/{$asset->uuid}/watermarked";
        $path = "{$directory}/{$identity}.webp";
        $manifestPath = "{$directory}/{$identity}.json";

        if ($this->isCurrent($path, $manifestPath, $fingerprint)) {
            return [
                'path' => $path,
                'fingerprint' => $fingerprint,
                'watermarked' => $configuration['enabled'] && $configuration['logo_path'] !== null,
            ];
        }

        $contents = Storage::disk($sourceDisk)->get($sourcePath);
        $image = $this->manager->decode($contents);
        $image->scaleDown(width: self::MAX_WIDTH, height: self::MAX_HEIGHT);

        $watermarked = false;

        if ($configuration['enabled'] && $configuration['logo_path'] !== null) {
            $this->applyWatermark($image, $configuration);
            $watermarked = true;
        }

        $encoded = (string) $image->encodeUsingFileExtension('webp', quality: 86);

        Storage::disk(self::OUTPUT_DISK)->put($path, $encoded, ['visibility' => 'public']);
        Storage::disk(self::OUTPUT_DISK)->put($manifestPath, json_encode([
            'fingerprint' => $fingerprint,
            'source_disk' => $sourceDisk,
            'source_path' => $sourcePath,
            'watermarked' => $watermarked,
            'generated_at' => now()->toISOString(),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return [
            'path' => $path,
            'fingerprint' => $fingerprint,
            'watermarked' => $watermarked,
        ];
    }

    private function applyWatermark($image, array $configuration): void
    {
        $logoContents = Storage::disk('public')->get($configuration['logo_path']);
        $logo = $this->manager->decode($logoContents);

        $targetWidth = max(48, (int) round($image->width() * ($configuration['scale'] / 100)));
        $targetWidth = min($targetWidth, max(48, $image->width() - ($configuration['margin'] * 2)));
        $logo->scaleDown(width: $targetWidth);

        $targetHeightLimit = max(48, $image->height() - ($configuration['margin'] * 2));
        if ($logo->height() > $targetHeightLimit) {
            $logo->scaleDown(height: $targetHeightLimit);
        }

        $image->insert(
            image: $logo,
            x: $configuration['position'] === 'center' ? 0 : $configuration['margin'],
            y: $configuration['position'] === 'center' ? 0 : $configuration['margin'],
            alignment: Alignment::create($configuration['position']),
            transparency: $configuration['opacity'] / 100,
        );
    }

    /**
     * @return array{enabled:bool,opacity:int,position:string,scale:int,margin:int,logo_path:?string,logo_fingerprint:?string}
     */
    private function configuration(): array
    {
        $logoValue = $this->settings->get('branding.watermark_logo');
        $logoPath = $this->brandingPath(is_string($logoValue) ? $logoValue : null);

        return [
            'enabled' => (bool) $this->settings->get('branding.watermark_enabled', true),
            'opacity' => min(100, max(10, (int) $this->settings->get('branding.watermark_opacity', 70))),
            'position' => $this->position((string) $this->settings->get('branding.watermark_position', 'center')),
            'scale' => min(100, max(5, (int) $this->settings->get('branding.watermark_scale', 35))),
            'margin' => min(200, max(0, (int) $this->settings->get('branding.watermark_margin', 24))),
            'logo_path' => $logoPath,
            'logo_fingerprint' => $logoPath ? $this->storageFingerprint('public', $logoPath) : null,
        ];
    }

    private function brandingPath(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH) ?: $value;
        $marker = '/storage/';
        $position = strpos($path, $marker);

        if ($position !== false) {
            $path = substr($path, $position + strlen($marker));
        }

        $path = ltrim($path, '/');

        return $path !== '' && Storage::disk('public')->exists($path)
            ? $path
            : null;
    }

    private function position(string $position): string
    {
        return in_array($position, ['center', 'top-left', 'top-right', 'bottom-left', 'bottom-right'], true)
            ? $position
            : 'center';
    }

    private function storageFingerprint(string $disk, string $path): string
    {
        if (! Storage::disk($disk)->exists($path)) {
            throw new RuntimeException("Preview source does not exist: {$disk}:{$path}");
        }

        return hash('sha256', implode('|', [
            $disk,
            $path,
            (string) Storage::disk($disk)->size($path),
            (string) Storage::disk($disk)->lastModified($path),
        ]));
    }

    private function isCurrent(string $path, string $manifestPath, string $fingerprint): bool
    {
        if (! Storage::disk(self::OUTPUT_DISK)->exists($path)
            || ! Storage::disk(self::OUTPUT_DISK)->exists($manifestPath)) {
            return false;
        }

        $manifest = json_decode(Storage::disk(self::OUTPUT_DISK)->get($manifestPath), true);

        return is_array($manifest) && ($manifest['fingerprint'] ?? null) === $fingerprint;
    }

    private function response(string $path): BinaryFileResponse
    {
        return response()->file(Storage::disk(self::OUTPUT_DISK)->path($path), [
            'Content-Type' => 'image/webp',
            'Content-Disposition' => 'inline; filename="preview.webp"',
            'Cache-Control' => 'public, max-age=86400, immutable',
            'X-Content-Type-Options' => 'nosniff',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ]);
    }
}

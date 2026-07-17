<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AssetPresentationService
{
    public function __construct(
        private readonly AssetWatermarkPreviewService $watermarks,
    ) {}

    public function saveMarketplace(
        Asset $asset,
        UploadedFile $image,
        array $editData,
        ?int $sourceAssetFileId = null,
    ): array {
        if (
            $sourceAssetFileId !== null
            && ! $asset->files()->whereKey($sourceAssetFileId)->exists()
        ) {
            throw new InvalidArgumentException(
                'The selected marketplace source file does not belong to this asset.',
            );
        }

        $disk = 'public';
        $directory = "assets/{$asset->uuid}/presentation/marketplace";
        $extension = strtolower(
            $image->guessExtension()
            ?: $image->getClientOriginalExtension()
            ?: 'jpg',
        );
        $filename = 'marketplace-card-'.Str::uuid().'.'.$extension;
        $path = $image->storeAs($directory, $filename, $disk);

        if (! is_string($path) || $path === '') {
            throw new InvalidArgumentException(
                'The marketplace image could not be stored.',
            );
        }

        $presentation = $asset->presentation_images ?? [];
        $previousPath = data_get($presentation, 'marketplace.path');

        $presentation['marketplace'] = [
            'disk' => $disk,
            'path' => $path,
            'source_asset_file_id' => $sourceAssetFileId,
            'edit_data' => $editData,
            'updated_at' => now()->toISOString(),
        ];

        $asset->forceFill([
            'presentation_images' => $presentation,
        ])->save();

        if (
            is_string($previousPath)
            && $previousPath !== ''
            && $previousPath !== $path
        ) {
            Storage::disk($disk)->delete($previousPath);
        }

        return $presentation['marketplace'];
    }

    public function clearMarketplace(Asset $asset): void
    {
        $presentation = $asset->presentation_images ?? [];
        $path = data_get($presentation, 'marketplace.path');
        $disk = data_get($presentation, 'marketplace.disk', 'public');

        unset($presentation['marketplace']);

        $asset->forceFill([
            'presentation_images' => $presentation ?: null,
        ])->save();

        if (is_string($path) && $path !== '') {
            Storage::disk($disk)->delete($path);
        }
    }

    public function marketplaceUrl(Asset $asset, bool $watermarked = false): ?string
    {
        $path = data_get($asset->presentation_images, 'marketplace.path');
        $disk = data_get($asset->presentation_images, 'marketplace.disk', 'public');

        if (! is_string($path) || $path === '') {
            return null;
        }

        return $watermarked
            ? $this->watermarks->marketplaceRoute($asset)
            : Storage::disk($disk)->url($path);
    }
}

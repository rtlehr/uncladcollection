<?php

namespace App\Services;

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileRole;
use App\Enums\AssetFileScanStatus;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Events\AssetCreated;
use App\Events\AssetFileAdded;
use App\Events\AssetFileRemoved;
use App\Jobs\ProcessAssetFile;
use App\Models\Asset;
use App\Models\AssetFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AssetService
{
    public function __construct(
        private readonly AssetStorageService $storageService,
        private readonly AssetValidationService $validationService,
        private readonly AssetFileRoleResolver $roleResolver,
    ) {}

    public function create(array $attributes): Asset
    {
        $asset = Asset::query()->create([
            ...$attributes,
            'uuid' => $attributes['uuid'] ?? (string) Str::uuid(),
            'asset_type' => $attributes['asset_type'] ?? AssetType::Image,
            'status' => $attributes['status'] ?? AssetStatus::Draft,
        ]);

        event(new AssetCreated($asset));

        return $asset;
    }

    public function addFile(
        Asset $asset,
        UploadedFile $file,
        ?AssetFileRole $role = null,
        bool $process = true,
        array $attributes = [],
    ): AssetFile {
        $resolved = $this->validationService->validate($file);
        $role ??= $this->roleResolver->resolve($resolved->mediaType);
        $stored = $this->storageService->store($asset, $file, $role);

        try {
            $assetFile = DB::transaction(function () use ($asset, $file, $role, $resolved, $stored, $attributes): AssetFile {
                $sortOrder = $attributes['sort_order'] ?? ((int) $asset->files()->max('sort_order') + 10);

                return $asset->files()->create([
                    ...$attributes,
                    'uuid' => $attributes['uuid'] ?? (string) Str::uuid(),
                    'role' => $role,
                    'media_type' => $resolved->mediaType,
                    'disk' => $stored['disk'],
                    'directory' => $stored['directory'],
                    'stored_filename' => $stored['stored_filename'],
                    'original_filename' => basename($file->getClientOriginalName()),
                    'extension' => $resolved->extension,
                    'mime_type' => $resolved->mimeType,
                    'size_bytes' => $file->getSize() ?: null,
                    'checksum_sha256' => $stored['checksum_sha256'],
                    'sort_order' => $sortOrder,
                    'processing_status' => AssetFileProcessingStatus::Pending,
                    'virus_scan_status' => AssetFileScanStatus::Pending,
                    'is_downloadable' => $attributes['is_downloadable'] ?? true,
                    'is_active' => $attributes['is_active'] ?? true,
                    'is_legacy' => false,
                ]);
            });
        } catch (\Throwable $exception) {
            $this->storageService->deleteStoredPath($stored['disk'], $stored['path']);
            throw $exception;
        }

        event(new AssetFileAdded($assetFile));

        if ($process) {
            ProcessAssetFile::dispatch($assetFile->id)->afterCommit();
        }

        return $assetFile;
    }

    public function replaceFile(
        AssetFile $existing,
        UploadedFile $replacement,
        bool $process = true,
    ): AssetFile {
        if ($existing->trashed()) {
            throw new InvalidArgumentException('A deleted asset file cannot be replaced.');
        }

        $metadata = array_merge($existing->metadata ?? [], [
            'replaces_asset_file_id' => $existing->id,
            'replaces_asset_file_uuid' => $existing->uuid,
        ]);

        $newFile = $this->addFile(
            asset: $existing->asset,
            file: $replacement,
            role: $existing->role,
            process: $process,
            attributes: [
                'sort_order' => $existing->sort_order,
                'is_downloadable' => $existing->is_downloadable,
                'metadata' => $metadata,
            ],
        );

        $this->removeFile($existing, purgePhysicalFile: false);

        return $newFile;
    }

    public function removeFile(AssetFile $assetFile, bool $purgePhysicalFile = false): void
    {
        DB::transaction(function () use ($assetFile): void {
            $asset = $assetFile->asset()->lockForUpdate()->firstOrFail();

            $updates = [];

            if ($asset->primary_preview_file_id === $assetFile->id) {
                $updates['primary_preview_file_id'] = null;
            }

            if ($asset->poster_file_id === $assetFile->id) {
                $updates['poster_file_id'] = null;
            }

            if ($updates !== []) {
                $asset->update($updates);
            }

            $assetFile->update(['is_active' => false]);
            $assetFile->delete();
        });

        if ($purgePhysicalFile) {
            $this->storageService->delete($assetFile);
        }

        event(new AssetFileRemoved($assetFile));
    }

    public function setPrimaryPreview(Asset $asset, ?AssetFile $assetFile): Asset
    {
        $this->assertBelongsToAsset($asset, $assetFile);
        $asset->update(['primary_preview_file_id' => $assetFile?->id]);

        return $asset->fresh();
    }

    public function setPoster(Asset $asset, ?AssetFile $assetFile): Asset
    {
        $this->assertBelongsToAsset($asset, $assetFile);
        $asset->update(['poster_file_id' => $assetFile?->id]);

        return $asset->fresh();
    }

    private function assertBelongsToAsset(Asset $asset, ?AssetFile $assetFile): void
    {
        if ($assetFile && $assetFile->asset_id !== $asset->id) {
            throw new InvalidArgumentException('The selected file does not belong to this asset.');
        }
    }
}

<?php

namespace App\Services\DesignStudio;

use App\Models\DesignProject;
use App\Models\DesignProjectAsset;
use App\Models\AssetFile;
use App\Models\License;
use Illuminate\Support\Collection;

class DesignProjectAssetService
{
    /**
     * Validate every licensed UC Library layer and persist the project's durable
     * licensed-asset manifest. Returns the unique references that were synced.
     *
     * @param array<string, mixed> $designJson
     * @return Collection<int, array{license_id:int,asset_id:int,asset_file_id:int}>
     */
    public function validateAndSync(int $userId, DesignProject $project, array $designJson): Collection
    {
        $objects = $designJson['fabric']['objects'] ?? [];
        $references = $this->licensedAssetReferences(is_array($objects) ? $objects : []);

        abort_if(
            $references->contains(fn (array $reference) => $reference['license_id'] <= 0 || $reference['asset_id'] <= 0),
            422,
            'A UC Library layer is missing its license reference.',
        );

        $references = $references
            ->unique(fn (array $reference) => $reference['license_id'].'-'.$reference['asset_id'].'-'.$reference['asset_file_id'])
            ->values();

        $licenses = $references->isEmpty()
            ? collect()
            : License::query()
                ->where('user_id', $userId)
                ->whereIn('id', $references->pluck('license_id'))
                ->get()
                ->keyBy('id');

        foreach ($references as $reference) {
            /** @var License|null $license */
            $license = $licenses->get($reference['license_id']);
            abort_unless(
                $license && $license->isActive() && (int) $license->asset_id === (int) $reference['asset_id'],
                403,
                'One or more UC Library images in this design no longer have an active matching license.',
            );
        }

        $fileIds = $references->pluck('asset_file_id')->filter(fn (int $id) => $id > 0)->unique();
        $assetFiles = $fileIds->isEmpty()
            ? collect()
            : AssetFile::query()->whereIn('id', $fileIds)->get(['id', 'asset_id'])->keyBy('id');

        foreach ($references as $reference) {
            $file = $reference['asset_file_id'] > 0 ? $assetFiles->get($reference['asset_file_id']) : null;
            abort_if(
                $file && (int) $file->asset_id !== (int) $reference['asset_id'],
                422,
                'A UC Library layer has an invalid asset-file reference.',
            );
        }

        $manifest = $references->map(function (array $reference) use ($assetFiles, $project): array {
            $file = $reference['asset_file_id'] > 0 ? $assetFiles->get($reference['asset_file_id']) : null;

            return [
                'design_project_id' => $project->id,
                'license_id' => $reference['license_id'],
                'asset_id' => $reference['asset_id'],
                // A deleted/replaced historical file is intentionally stored as null.
                // The active asset license remains the export entitlement.
                'asset_file_id' => $file?->id,
            ];
        });

        // Retain the original project source license as part of the manifest,
        // even for older projects whose background predates Fabric source metadata.
        if ($project->license_id && $project->asset_id) {
            $manifest->push([
                'design_project_id' => $project->id,
                'license_id' => (int) $project->license_id,
                'asset_id' => (int) $project->asset_id,
                'asset_file_id' => null,
            ]);
        }

        DesignProjectAsset::query()->where('design_project_id', $project->id)->delete();
        foreach ($manifest->unique(fn (array $row) => implode('-', [$row['license_id'], $row['asset_id'], $row['asset_file_id'] ?? 0])) as $row) {
            DesignProjectAsset::create($row);
        }

        return $references;
    }

    /**
     * Recursively collect licensed UC Library references from Fabric objects.
     * Group objects serialize their children inside an `objects` array, so a
     * top-level-only scan would miss licensed images placed inside groups.
     *
     * @param array<int, mixed> $objects
     * @return Collection<int, array{license_id:int,asset_id:int,asset_file_id:int}>
     */
    private function licensedAssetReferences(array $objects): Collection
    {
        $references = collect();

        foreach ($objects as $object) {
            if (! is_array($object)) {
                continue;
            }

            if (($object['sourceType'] ?? null) === 'licensed_asset') {
                $references->push([
                    'license_id' => (int) ($object['sourceLicenseId'] ?? 0),
                    'asset_id' => (int) ($object['sourceAssetId'] ?? 0),
                    'asset_file_id' => (int) ($object['sourceAssetFileId'] ?? 0),
                ]);
            }

            $children = $object['objects'] ?? null;
            if (is_array($children)) {
                $references = $references->concat($this->licensedAssetReferences($children));
            }
        }

        return $references;
    }
}

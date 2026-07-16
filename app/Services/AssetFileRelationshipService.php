<?php

namespace App\Services;

use App\Enums\AssetFileRelationshipType;
use App\Models\Asset;
use App\Models\AssetFileRelationship;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssetFileRelationshipService
{
    public function saveMany(Asset $asset, array $relationships): void
    {
        $allowedFileIds = $asset->files()
            ->withTrashed()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $normalized = collect($relationships)
            ->values()
            ->map(function (array $relationship, int $index) use (
                $asset,
                $allowedFileIds,
            ): array {
                $sourceId = (int) $relationship['source_asset_file_id'];
                $targetId = (int) $relationship['target_asset_file_id'];

                if ($sourceId === $targetId) {
                    throw new InvalidArgumentException(
                        'A file cannot be related to itself.',
                    );
                }

                if (
                    ! in_array($sourceId, $allowedFileIds, true)
                    || ! in_array($targetId, $allowedFileIds, true)
                ) {
                    throw new InvalidArgumentException(
                        'Both relationship files must belong to the same asset.',
                    );
                }

                $type = AssetFileRelationshipType::from(
                    $relationship['relationship_type'],
                );

                return [
                    'asset_id' => $asset->id,
                    'source_asset_file_id' => $sourceId,
                    'target_asset_file_id' => $targetId,
                    'relationship_type' => $type,
                    'label' => filled($relationship['label'] ?? null)
                        ? trim((string) $relationship['label'])
                        : null,
                    'sort_order' => (int) (
                        $relationship['sort_order']
                        ?? (($index + 1) * 10)
                    ),
                    'metadata' => is_array($relationship['metadata'] ?? null)
                        ? $relationship['metadata']
                        : null,
                ];
            });

        $duplicate = $normalized
            ->groupBy(fn (array $item) => implode(':', [
                $item['source_asset_file_id'],
                $item['target_asset_file_id'],
                $item['relationship_type']->value,
            ]))
            ->first(fn ($group) => $group->count() > 1);

        if ($duplicate) {
            throw new InvalidArgumentException(
                'Duplicate file relationships are not allowed.',
            );
        }

        DB::transaction(function () use ($asset, $normalized): void {
            $asset->fileRelationships()->delete();

            foreach ($normalized as $relationship) {
                AssetFileRelationship::query()->create($relationship);
            }
        });
    }
}

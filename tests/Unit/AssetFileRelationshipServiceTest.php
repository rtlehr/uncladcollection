<?php

namespace Tests\Unit;

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileRelationshipType;
use App\Enums\AssetFileRole;
use App\Enums\AssetFileScanStatus;
use App\Enums\AssetMediaType;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Services\AssetFileRelationshipService;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class AssetFileRelationshipServiceTest extends TestCase
{
    public function test_duplicate_relationships_are_rejected(): void
    {
        $asset = Asset::factory()->create();
        $source = $this->file($asset, 'preview.jpg');
        $target = $this->file($asset, 'video.mp4');

        $this->expectException(InvalidArgumentException::class);

        app(AssetFileRelationshipService::class)->saveMany(
            $asset,
            [
                [
                    'source_asset_file_id' => $source->id,
                    'target_asset_file_id' => $target->id,
                    'relationship_type' =>
                        AssetFileRelationshipType::PosterFor->value,
                ],
                [
                    'source_asset_file_id' => $source->id,
                    'target_asset_file_id' => $target->id,
                    'relationship_type' =>
                        AssetFileRelationshipType::PosterFor->value,
                ],
            ],
        );
    }

    public function test_saving_replaces_previous_relationship_set(): void
    {
        $asset = Asset::factory()->create();
        $first = $this->file($asset, 'preview.jpg');
        $second = $this->file($asset, 'source.eps');
        $third = $this->file($asset, 'package.zip');

        $service = app(AssetFileRelationshipService::class);

        $service->saveMany($asset, [
            [
                'source_asset_file_id' => $first->id,
                'target_asset_file_id' => $second->id,
                'relationship_type' =>
                    AssetFileRelationshipType::Represents->value,
            ],
        ]);

        $service->saveMany($asset, [
            [
                'source_asset_file_id' => $third->id,
                'target_asset_file_id' => $second->id,
                'relationship_type' =>
                    AssetFileRelationshipType::Contains->value,
            ],
        ]);

        $this->assertDatabaseMissing('asset_file_relationships', [
            'source_asset_file_id' => $first->id,
            'target_asset_file_id' => $second->id,
        ]);

        $this->assertDatabaseHas('asset_file_relationships', [
            'source_asset_file_id' => $third->id,
            'target_asset_file_id' => $second->id,
            'relationship_type' =>
                AssetFileRelationshipType::Contains->value,
        ]);
    }

    private function file(Asset $asset, string $name): AssetFile
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return $asset->files()->create([
            'uuid' => (string) Str::uuid(),
            'role' => AssetFileRole::Supplemental,
            'media_type' => AssetMediaType::Other,
            'disk' => 'asset-files',
            'directory' => 'tests',
            'stored_filename' => Str::uuid().'.'.$extension,
            'original_filename' => $name,
            'extension' => $extension,
            'mime_type' => 'application/octet-stream',
            'size_bytes' => 100,
            'sort_order' => 10,
            'processing_status' => AssetFileProcessingStatus::Ready,
            'virus_scan_status' => AssetFileScanStatus::Clean,
            'is_downloadable' => true,
            'is_active' => true,
            'is_legacy' => false,
        ]);
    }
}

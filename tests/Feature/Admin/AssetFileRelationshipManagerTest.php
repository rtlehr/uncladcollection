<?php

namespace Tests\Feature\Admin;

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileRelationshipType;
use App\Enums\AssetFileRole;
use App\Enums\AssetFileScanStatus;
use App\Enums\AssetMediaType;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssetFileRelationshipManagerTest extends TestCase
{
    public function test_admin_can_save_file_relationships(): void
    {
        $asset = Asset::factory()->create();
        $preview = $this->file($asset, 'preview.jpg');
        $vector = $this->file($asset, 'artwork.eps');

        $response = $this->actingAs($this->assetAdministrator())->put(
            "/admin/assets/{$asset->id}/relationships",
            [
                'relationships' => [
                    [
                        'source_asset_file_id' => $preview->id,
                        'target_asset_file_id' => $vector->id,
                        'relationship_type' =>
                            AssetFileRelationshipType::Represents->value,
                        'label' => 'Preview for vector artwork',
                        'sort_order' => 10,
                    ],
                ],
            ],
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('asset_file_relationships', [
            'asset_id' => $asset->id,
            'source_asset_file_id' => $preview->id,
            'target_asset_file_id' => $vector->id,
            'relationship_type' =>
                AssetFileRelationshipType::Represents->value,
            'label' => 'Preview for vector artwork',
        ]);
    }

    public function test_relationship_files_must_belong_to_same_asset(): void
    {
        $asset = Asset::factory()->create();
        $otherAsset = Asset::factory()->create();
        $source = $this->file($asset, 'preview.jpg');
        $foreignTarget = $this->file($otherAsset, 'foreign.eps');

        $response = $this->actingAs($this->assetAdministrator())->put(
            "/admin/assets/{$asset->id}/relationships",
            [
                'relationships' => [
                    [
                        'source_asset_file_id' => $source->id,
                        'target_asset_file_id' => $foreignTarget->id,
                        'relationship_type' =>
                            AssetFileRelationshipType::Represents->value,
                    ],
                ],
            ],
        );

        $response->assertSessionHasErrors(
            'relationships.0.target_asset_file_id',
        );
    }

    public function test_file_cannot_be_related_to_itself(): void
    {
        $asset = Asset::factory()->create();
        $file = $this->file($asset, 'preview.jpg');

        $response = $this->actingAs($this->assetAdministrator())->put(
            "/admin/assets/{$asset->id}/relationships",
            [
                'relationships' => [
                    [
                        'source_asset_file_id' => $file->id,
                        'target_asset_file_id' => $file->id,
                        'relationship_type' =>
                            AssetFileRelationshipType::CompanionTo->value,
                    ],
                ],
            ],
        );

        $response->assertSessionHasErrors(
            'relationships.0.target_asset_file_id',
        );
    }

    private function file(Asset $asset, string $name): AssetFile
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return $asset->files()->create([
            'uuid' => (string) Str::uuid(),
            'role' => AssetFileRole::Supplemental,
            'media_type' => in_array($extension, ['jpg', 'png'], true)
                ? AssetMediaType::Image
                : AssetMediaType::Vector,
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

    private function assetAdministrator(): User
    {
        $user = User::factory()->create();

        $permissions = collect([
            [
                'name' => 'view_admin',
                'label' => 'View Admin',
                'group_name' => 'Administration',
            ],
            [
                'name' => 'manage_images',
                'label' => 'Manage Images and Assets',
                'group_name' => 'Assets',
            ],
        ])->map(
            fn (array $attributes) => Permission::query()->firstOrCreate(
                ['name' => $attributes['name']],
                [
                    'label' => $attributes['label'],
                    'group_name' => $attributes['group_name'],
                    'description' => null,
                    'is_system' => true,
                    'is_locked' => false,
                ],
            ),
        );

        $user->permissions()->syncWithoutDetaching(
            $permissions->pluck('id')->all(),
        );

        return $user;
    }
}

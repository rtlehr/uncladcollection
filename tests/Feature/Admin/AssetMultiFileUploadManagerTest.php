<?php

namespace Tests\Feature\Admin;

use App\Enums\AssetFileRole;
use App\Models\Asset;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetMultiFileUploadManagerTest extends TestCase
{
    public function test_existing_asset_accepts_multiple_files_with_roles(): void
    {
        Storage::fake('public');

        $user = $this->assetAdministrator();
        $asset = Asset::factory()->create();

        $response = $this->actingAs($user)->post(
            "/admin/assets/{$asset->id}/files",
            [
                'files' => [
                    UploadedFile::fake()->image('preview.jpg', 1200, 800),
                    UploadedFile::fake()->create(
                        'source.zip',
                        250,
                        'application/zip',
                    ),
                ],
                'file_roles' => [
                    AssetFileRole::Preview->value,
                    AssetFileRole::Bundle->value,
                ],
                'file_downloadable' => [0, 1],
            ],
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('asset_files', [
            'asset_id' => $asset->id,
            'original_filename' => 'preview.jpg',
            'role' => AssetFileRole::Preview->value,
            'is_downloadable' => false,
        ]);

        $this->assertDatabaseHas('asset_files', [
            'asset_id' => $asset->id,
            'original_filename' => 'source.zip',
            'role' => AssetFileRole::Bundle->value,
            'is_downloadable' => true,
        ]);
    }

    public function test_roles_must_match_uploaded_file_count(): void
    {
        $user = $this->assetAdministrator();
        $asset = Asset::factory()->create();

        $response = $this->actingAs($user)->post(
            "/admin/assets/{$asset->id}/files",
            [
                'files' => [
                    UploadedFile::fake()->image('preview.jpg'),
                    UploadedFile::fake()->image('second.jpg'),
                ],
                'file_roles' => [
                    AssetFileRole::Preview->value,
                ],
                'file_downloadable' => [1, 1],
            ],
        );

        $response->assertSessionHasErrors('file_roles');
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

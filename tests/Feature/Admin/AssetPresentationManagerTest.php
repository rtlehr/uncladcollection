<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetPresentationManagerTest extends TestCase
{
    public function test_admin_can_save_a_marketplace_image(): void
    {
        Storage::fake('public');

        $asset = Asset::factory()->create();
        $user = $this->assetAdministrator();

        $response = $this->actingAs($user)->post(
            "/admin/assets/{$asset->id}/presentation",
            [
                'marketplace_image' => UploadedFile::fake()->image(
                    'marketplace.jpg',
                    1200,
                    675,
                ),
                'marketplace_edit_data' => json_encode([
                    'preset' => 'marketplace-card',
                    'zoom' => 1.2,
                    'offsetX' => 10,
                    'offsetY' => -5,
                    'rotation' => 0,
                    'focusX' => 0.5,
                    'focusY' => 0.5,
                    'outputWidth' => 1200,
                    'outputHeight' => 675,
                ]),
            ],
        );

        $response->assertRedirect();

        $asset->refresh();

        $path = data_get(
            $asset->presentation_images,
            'marketplace.path',
        );

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame(
            'marketplace-card',
            data_get(
                $asset->presentation_images,
                'marketplace.edit_data.preset',
            ),
        );
    }

    public function test_admin_can_clear_the_marketplace_image(): void
    {
        Storage::fake('public');

        $asset = Asset::factory()->create([
            'presentation_images' => [
                'marketplace' => [
                    'disk' => 'public',
                    'path' => 'assets/example/marketplace.jpg',
                    'edit_data' => [],
                ],
            ],
        ]);
        Storage::disk('public')->put(
            'assets/example/marketplace.jpg',
            'image',
        );

        $response = $this->actingAs($this->assetAdministrator())->post(
            "/admin/assets/{$asset->id}/presentation",
            ['remove_marketplace_image' => true],
        );

        $response->assertRedirect();

        $asset->refresh();

        $this->assertNull(
            data_get($asset->presentation_images, 'marketplace'),
        );
        Storage::disk('public')->assertMissing(
            'assets/example/marketplace.jpg',
        );
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

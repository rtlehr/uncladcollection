<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogUnifiedImageWorkflowTest extends TestCase
{
    public function test_asset_library_crop_returns_asset_metadata(): void
    {
        Storage::fake('public');

        $asset = Asset::factory()->create([
            'title' => 'Library Sunset',
            'slug' => 'library-sunset',
            'photographer' => 'Test Photographer',
        ]);

        $response = $this->actingAs($this->blogAdministrator())->post(
            '/admin/blog-posts/upload-content-image',
            [
                'image' => UploadedFile::fake()
                    ->image('library-crop.jpg', 1200, 800),
                'preset' => 'blog-content-landscape',
                'edit_data' => json_encode([
                    'preset' => 'blog-content-landscape',
                ]),
                'asset_id' => $asset->id,
                'alt' => 'Edited library sunset',
            ],
            ['Accept' => 'application/json'],
        );

        $response->assertOk()
            ->assertJsonPath('asset.id', $asset->id)
            ->assertJsonPath('asset.slug', 'library-sunset')
            ->assertJsonPath(
                'asset.photographer',
                'Test Photographer',
            )
            ->assertJsonPath('alt', 'Edited library sunset');

        Storage::disk('public')->assertExists(
            $response->json('path'),
        );
    }

    public function test_local_crop_still_returns_without_asset_metadata(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->blogAdministrator())->post(
            '/admin/blog-posts/upload-content-image',
            [
                'image' => UploadedFile::fake()
                    ->image('local.jpg', 300, 300),
                'preset' => 'blog-content-square',
                'edit_data' => json_encode([
                    'preset' => 'blog-content-square',
                ]),
                'alt' => 'Local square image',
            ],
            ['Accept' => 'application/json'],
        );

        $response->assertOk()
            ->assertJsonPath('asset', null)
            ->assertJsonPath('alt', 'Local square image');

        Storage::disk('public')->assertExists(
            $response->json('path'),
        );
    }

    private function blogAdministrator(): User
    {
        $user = User::factory()->create();

        $permissions = collect([
            ['name' => 'view_admin', 'label' => 'View Admin'],
            ['name' => 'manage_blog_posts', 'label' => 'Manage Blog Posts'],
        ])->map(fn (array $data) => Permission::query()->firstOrCreate(
            ['name' => $data['name']],
            [
                'label' => $data['label'],
                'group_name' => 'Blog',
                'description' => null,
                'is_system' => true,
                'is_locked' => false,
            ],
        ));

        $user->permissions()->syncWithoutDetaching(
            $permissions->pluck('id')->all(),
        );

        return $user;
    }
}

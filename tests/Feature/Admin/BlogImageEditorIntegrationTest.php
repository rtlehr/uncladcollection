<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlogImageEditorIntegrationTest extends TestCase
{
    public function test_admin_can_create_post_with_edited_header_and_icon(): void
    {
        Storage::fake('public');

        $slug = 'edited-blog-images-'.Str::lower(Str::random(10));

        $response = $this->actingAs($this->blogAdministrator())->post(
            '/admin/blog-posts',
            [
                'title' => 'Edited Blog Images',
                'slug' => $slug,
                'content' => '<p>Article</p>',
                'header_image' => UploadedFile::fake()
                    ->image('header.jpg', 1800, 688),
                'header_image_original' => UploadedFile::fake()
                    ->image('header-original.jpg', 2400, 1600),
                'header_image_edit_data' => json_encode([
                    'preset' => 'blog-header',
                    'zoom' => 1.2,
                ]),
                'icon_image' => UploadedFile::fake()
                    ->image('icon.jpg', 600, 600),
                'icon_image_original' => UploadedFile::fake()
                    ->image('icon-original.jpg', 1200, 1200),
                'icon_image_edit_data' => json_encode([
                    'preset' => 'blog-icon',
                    'zoom' => 1,
                ]),
                'status' => 'draft',
                'is_active' => true,
                'comments_enabled' => true,
                'comments_visible' => true,
                'comments_require_approval' => false,
                'category_ids' => [],
                'tag_ids' => [],
            ],
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('blog_posts', [
            'slug' => $slug,
        ]);

        $post = \App\Models\BlogPost::query()
            ->where('slug', $slug)
            ->firstOrFail();

        Storage::disk('public')->assertExists($post->header_image_path);
        Storage::disk('public')->assertExists(
            $post->header_image_original_path,
        );
        Storage::disk('public')->assertExists($post->icon_image_path);
        $this->assertSame(
            'blog-header',
            data_get($post->image_edit_data, 'header.preset'),
        );
    }

    public function test_article_image_endpoint_accepts_only_blog_presets(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->blogAdministrator())->post(
            '/admin/blog-posts/upload-content-image',
            [
                'image' => UploadedFile::fake()
                    ->image('article.jpg', 1200, 800),
                'preset' => 'blog-content-landscape',
                'edit_data' => '{}',
            ],
            ['Accept' => 'application/json'],
        );

        $response->assertOk()
            ->assertJsonPath('preset', 'blog-content-landscape');

        Storage::disk('public')->assertExists(
            $response->json('path'),
        );
    }


    public function test_article_image_endpoint_accepts_square_preset(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->blogAdministrator())->post(
            '/admin/blog-posts/upload-content-image',
            [
                'image' => UploadedFile::fake()
                    ->image('square.jpg', 300, 300),
                'preset' => 'blog-content-square',
                'edit_data' => json_encode([
                    'preset' => 'blog-content-square',
                    'outputWidth' => 300,
                    'outputHeight' => 300,
                ]),
            ],
            ['Accept' => 'application/json'],
        );

        $response->assertOk()
            ->assertJsonPath('preset', 'blog-content-square');

        Storage::disk('public')->assertExists(
            $response->json('path'),
        );

        $this->assertStringContainsString(
            'blog/content-images/square/',
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

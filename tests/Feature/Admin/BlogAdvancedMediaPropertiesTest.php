<?php

namespace Tests\Feature\Admin;

use App\Models\BlogPost;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class BlogAdvancedMediaPropertiesTest extends TestCase
{
    public function test_blog_post_preserves_advanced_image_attributes(): void
    {
        $slug = 'advanced-media-'.Str::lower(Str::random(10));

        $content = <<<'HTML'
<p>Before image.</p>
<img
    src="/storage/blog/content-images/landscape/example.jpg"
    alt="Sunset over the ocean"
    class="blog-image-center blog-image-landscape-inline"
    data-image-id="12"
    data-image-slug="sunset"
    data-photographer="Jane Smith"
    data-public-url="/assets/sunset"
    data-caption="A calm evening at the coast."
    data-credit="Photo by Jane Smith"
    data-asset-title="Coastal Sunset"
    data-show-license-link="true"
    data-click-to-enlarge="true"
    data-border-style="card"
    data-shadow-style="soft"
    data-rounded-style="large"
    data-spacing-style="normal"
/>
HTML;

        $response = $this->actingAs($this->blogAdministrator())->post(
            '/admin/blog-posts',
            [
                'title' => 'Advanced Media',
                'slug' => $slug,
                'content' => $content,
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

        $post = BlogPost::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $this->assertStringContainsString(
            'data-caption="A calm evening at the coast."',
            $post->content,
        );
        $this->assertStringContainsString(
            'data-show-license-link="true"',
            $post->content,
        );
        $this->assertStringContainsString(
            'data-shadow-style="soft"',
            $post->content,
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

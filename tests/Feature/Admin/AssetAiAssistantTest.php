<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AssetAiAssistantTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_admin_can_request_and_apply_ai_asset_suggestions(): void
    {
        Storage::fake('public');
        config()->set('ai-assets.api_key', 'test-key');
        config()->set('ai-assets.model', 'gpt-4.1-mini');

        Storage::disk('public')->put('assets/demo/presentation/marketplace/test.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2n2kAAAAASUVORK5CYII='
        ));

        $asset = Asset::factory()->create([
            'presentation_images' => [
                'marketplace' => [
                    'disk' => 'public',
                    'path' => 'assets/demo/presentation/marketplace/test.png',
                ],
            ],
        ]);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output' => [[
                    'content' => [[
                        'text' => json_encode([
                            'title' => 'Quiet Morning by the Water',
                            'description' => 'A calm outdoor lifestyle scene beside the water.',
                            'alt_text' => 'Adults relaxing beside calm water in a natural setting.',
                            'seo_title' => 'Quiet Waterside Lifestyle Image',
                            'seo_description' => 'A peaceful outdoor lifestyle stock image beside calm water.',
                            'keywords' => ['outdoors', 'water', 'relaxation'],
                            'objects' => ['water', 'trees'],
                            'scene' => 'Outdoor waterside setting',
                            'composition' => 'Wide landscape composition',
                            'relationship_suggestions' => ['waterside collection'],
                        ]),
                    ]],
                ]],
                'usage' => ['input_tokens' => 100, 'output_tokens' => 80, 'total_tokens' => 180],
            ]),
        ]);

        $user = $this->assetAdministrator();

        $this->actingAs($user)->post("/admin/assets/{$asset->id}/ai-suggestions", [
            'adult_content_confirmed' => true,
            'non_sexual_content_confirmed' => true,
        ])->assertRedirect();

        $suggestion = $asset->aiSuggestions()->firstOrFail();
        $this->assertSame('completed', $suggestion->status);
        $this->assertSame('Quiet Morning by the Water', $suggestion->suggestions['title']);

        $this->actingAs($user)->post("/admin/assets/{$asset->id}/ai-suggestions/{$suggestion->id}/apply", [
            'fields' => ['title', 'alt_text', 'keywords', 'detected_objects'],
            'keyword_mode' => 'replace',
        ])->assertRedirect();

        $asset->refresh();
        $this->assertSame('Quiet Morning by the Water', $asset->title);
        $this->assertSame(['outdoors', 'water', 'relaxation'], $asset->keywords);
        $this->assertSame(['water', 'trees'], $asset->detected_objects);
    }

    public function test_confirmation_is_required_before_ai_analysis(): void
    {
        $asset = Asset::factory()->create();

        $this->actingAs($this->assetAdministrator())
            ->post("/admin/assets/{$asset->id}/ai-suggestions", [])
            ->assertSessionHasErrors(['adult_content_confirmed', 'non_sexual_content_confirmed']);
    }

    private function assetAdministrator(): User
    {
        $user = User::factory()->create();

        $permissions = collect([
            ['name' => 'view_admin', 'label' => 'View Admin', 'group_name' => 'Administration'],
            ['name' => 'manage_images', 'label' => 'Manage Images and Assets', 'group_name' => 'Assets'],
        ])->map(fn (array $attributes) => Permission::query()->firstOrCreate(
            ['name' => $attributes['name']],
            [...$attributes, 'description' => null, 'is_system' => true, 'is_locked' => false],
        ));

        $user->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());

        return $user;
    }
}

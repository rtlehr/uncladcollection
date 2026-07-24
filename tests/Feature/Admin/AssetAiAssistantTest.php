<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetAiAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_request_ollama_suggestions_and_apply_selected_metadata(): void
    {
        Storage::fake('public');
        $this->configureOllama();
        $asset = $this->assetWithPreview();

        Http::fake([
            'https://ai.example.test/api/chat' => Http::response($this->streamedMetadataResponse(), 200, [
                'Content-Type' => 'application/x-ndjson',
            ]),
        ]);

        $user = $this->assetAdministrator();

        $this->actingAs($user)->post("/admin/assets/{$asset->id}/ai-suggestions", [
            'non_sexual_content_confirmed' => true,
            'provider' => 'ollama',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $suggestion = $asset->aiSuggestions()->firstOrFail();
        $this->assertSame('completed', $suggestion->status);
        $this->assertSame('ollama', $suggestion->provider);
        $this->assertSame('qwen3-vl:8b', $suggestion->model);
        $this->assertSame('Quiet Morning by the Water', $suggestion->suggestions['title']);
        $this->assertSame(180, $suggestion->total_tokens);

        Http::assertSent(fn ($request): bool =>
            $request->url() === 'https://ai.example.test/api/chat'
            && $request->hasHeader('Authorization', 'Bearer test-ollama-token')
            && $request['model'] === 'qwen3-vl:8b'
            && $request['stream'] === true
            && $request['think'] === false
            && $request['messages'][0]['role'] === 'user'
            && count($request['messages'][0]['images']) === 1
        );

        $this->actingAs($user)->post("/admin/assets/{$asset->id}/ai-suggestions/{$suggestion->id}/apply", [
            'fields' => ['title', 'description', 'alt_text', 'seo_title', 'seo_description', 'keywords', 'dominant_colors', 'detected_objects'],
            'keyword_mode' => 'replace',
        ])->assertRedirect();

        $asset->refresh();
        $this->assertSame('Quiet Morning by the Water', $asset->title);
        $this->assertSame('A calm outdoor lifestyle scene beside the water.', $asset->description);
        $this->assertSame('Adults relaxing beside calm water in a natural setting.', $asset->alt_text);
        $this->assertSame('Quiet Waterside Lifestyle Image', $asset->seo_title);
        $this->assertSame('A peaceful outdoor lifestyle stock image beside calm water.', $asset->seo_description);
        $this->assertSame(['outdoors', 'water', 'relaxation'], $asset->keywords);
        $this->assertNotEmpty($asset->dominant_colors);
        $this->assertSame(['water', 'trees'], $asset->detected_objects);
    }

    public function test_ollama_can_use_valid_metadata_returned_in_thinking_output(): void
    {
        Storage::fake('public');
        $this->configureOllama();
        $asset = $this->assetWithPreview();

        $thinking = "I will return the requested object.\n".json_encode($this->metadata());
        $split = intdiv(strlen($thinking), 2);

        $body = implode("\n", [
            json_encode([
                'model' => 'qwen3-vl:8b',
                'message' => ['role' => 'assistant', 'content' => '', 'thinking' => substr($thinking, 0, $split)],
                'done' => false,
            ]),
            json_encode([
                'model' => 'qwen3-vl:8b',
                'message' => ['role' => 'assistant', 'content' => '', 'thinking' => substr($thinking, $split)],
                'done' => true,
                'done_reason' => 'stop',
                'prompt_eval_count' => 100,
                'eval_count' => 80,
            ]),
        ])."\n";

        Http::fake([
            'https://ai.example.test/api/chat' => Http::response($body, 200, [
                'Content-Type' => 'application/x-ndjson',
            ]),
        ]);

        $this->actingAs($this->assetAdministrator())
            ->post("/admin/assets/{$asset->id}/ai-suggestions", [
                    'non_sexual_content_confirmed' => true,
                'provider' => 'ollama',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $suggestion = $asset->aiSuggestions()->firstOrFail();
        $this->assertSame('completed', $suggestion->status);
        $this->assertSame('Quiet Morning by the Water', $suggestion->suggestions['title']);
    }

    public function test_openai_is_used_when_ollama_fails_and_fallback_is_enabled(): void
    {
        Storage::fake('public');
        $this->configureOllama();
        config()->set('ai-assets.fallback_enabled', true);
        config()->set('ai-assets.fallback_provider', 'openai');
        config()->set('ai-assets.providers.openai.api_key', 'test-openai-key');
        config()->set('ai-assets.providers.openai.base_url', 'https://api.openai.test/v1');
        config()->set('ai-assets.providers.openai.model', 'gpt-4.1-mini');

        $asset = $this->assetWithPreview();

        Http::fake([
            'https://ai.example.test/api/chat' => Http::response(['error' => 'temporary failure'], 503),
            'https://api.openai.test/v1/responses' => Http::response([
                'output' => [[
                    'content' => [[
                        'type' => 'output_text',
                        'text' => json_encode($this->metadata()),
                    ]],
                ]],
                'usage' => ['input_tokens' => 110, 'output_tokens' => 90, 'total_tokens' => 200],
            ]),
        ]);

        $this->actingAs($this->assetAdministrator())
            ->post("/admin/assets/{$asset->id}/ai-suggestions", [
                    'non_sexual_content_confirmed' => true,
                'provider' => 'ollama',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $suggestion = $asset->aiSuggestions()->firstOrFail();
        $this->assertSame('completed', $suggestion->status);
        $this->assertSame('openai', $suggestion->provider);
        $this->assertSame('gpt-4.1-mini', $suggestion->model);
        $this->assertSame(200, $suggestion->total_tokens);

        Http::assertSentCount(2);
    }

    public function test_confirmation_is_required_before_ai_analysis(): void
    {
        $asset = Asset::factory()->create();

        $this->actingAs($this->assetAdministrator())
            ->post("/admin/assets/{$asset->id}/ai-suggestions", [])
            ->assertSessionHasErrors(['non_sexual_content_confirmed']);
    }

    private function configureOllama(): void
    {
        config()->set('ai-assets.enabled', true);
        config()->set('ai-assets.default_provider', 'ollama');
        config()->set('ai-assets.fallback_enabled', false);
        config()->set('ai-assets.providers.ollama.base_url', 'https://ai.example.test');
        config()->set('ai-assets.providers.ollama.token', 'test-ollama-token');
        config()->set('ai-assets.providers.ollama.model', 'qwen3-vl:8b');
        config()->set('ai-assets.providers.ollama.retry_times', 1);
    }

    private function streamedMetadataResponse(): string
    {
        $json = json_encode($this->metadata());
        $split = intdiv(strlen($json), 2);

        return implode("\n", [
            json_encode([
                'model' => 'qwen3-vl:8b',
                'message' => ['role' => 'assistant', 'content' => substr($json, 0, $split)],
                'done' => false,
            ]),
            json_encode([
                'model' => 'qwen3-vl:8b',
                'message' => ['role' => 'assistant', 'content' => substr($json, $split)],
                'done' => true,
                'done_reason' => 'stop',
                'prompt_eval_count' => 100,
                'eval_count' => 80,
            ]),
        ])."\n";
    }

    private function assetWithPreview(): Asset
    {
        Storage::disk('public')->put('assets/demo/presentation/marketplace/test.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAAFklEQVR4nGNsWHCAgYGBiYGBgYGBAQAWagHkxZ4dhwAAAABJRU5ErkJggg=='
        ));

        return Asset::factory()->create([
            'presentation_images' => [
                'marketplace' => [
                    'disk' => 'public',
                    'path' => 'assets/demo/presentation/marketplace/test.png',
                ],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function metadata(): array
    {
        return [
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
        ];
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

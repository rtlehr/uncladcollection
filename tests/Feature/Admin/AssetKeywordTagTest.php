<?php

namespace Tests\Feature\Admin;

use App\Models\Asset;
use App\Models\Tag;
use App\Services\AssetTagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetKeywordTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_reuses_and_syncs_asset_keywords_as_image_tags(): void
    {
        $asset = Asset::factory()->create();
        $existing = Tag::query()->create([
            'name' => 'Beach',
            'slug' => 'beach',
            'tag_type' => 'image',
        ]);

        app(AssetTagService::class)->syncNames($asset, [
            'Beach',
            'Naturist Resort',
            'beach',
        ]);

        $asset->refresh()->load('tags');

        $this->assertCount(2, $asset->tags);
        $this->assertTrue($asset->tags->contains($existing));
        $this->assertDatabaseHas('tags', [
            'slug' => 'naturist-resort',
            'tag_type' => 'image',
        ]);
        $this->assertSame(['Beach', 'Naturist Resort'], $asset->keywords);
    }

    public function test_it_can_append_ai_keywords_without_removing_existing_tags(): void
    {
        $asset = Asset::factory()->create();
        $service = app(AssetTagService::class);

        $service->syncNames($asset, ['Beach']);
        $service->mergeNames($asset, ['Sunset', 'Beach']);

        $this->assertEqualsCanonicalizing(
            ['Beach', 'Sunset'],
            $asset->refresh()->tags()->pluck('name')->all(),
        );
    }
}

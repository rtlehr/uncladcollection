<?php

namespace Tests\Feature;

use App\Models\Asset;
use Tests\TestCase;

class AssetCardDataTest extends TestCase
{
    public function test_published_asset_card_data_is_available(): void
    {
        $asset = Asset::factory()->create([
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $response = $this->getJson(
            "/assets/{$asset->slug}/card-data",
        );

        $response->assertOk()
            ->assertJsonPath('asset.id', $asset->id)
            ->assertJsonPath('asset.slug', $asset->slug)
            ->assertJsonPath('asset.title', $asset->title);
    }

    public function test_draft_asset_card_data_is_hidden(): void
    {
        $asset = Asset::factory()->create([
            'status' => 'draft',
            'is_active' => true,
        ]);

        $this->getJson("/assets/{$asset->slug}/card-data")
            ->assertNotFound();
    }
}

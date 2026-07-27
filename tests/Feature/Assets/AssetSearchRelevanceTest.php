<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\AssetOffering;
use App\Models\LicenseType;
use App\Services\AssetSearchDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetSearchRelevanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_title_match_ranks_before_description_match(): void
    {
        $exact = $this->discoverableAsset(['title' => 'Sunset Beach', 'description' => 'A coastal scene.']);
        $description = $this->discoverableAsset(['title' => 'Coastal Walk', 'description' => 'A sunset beach in the distance.']);

        app(AssetSearchDocumentService::class)->rebuild($exact);
        app(AssetSearchDocumentService::class)->rebuild($description);

        $response = $this->get('/images?search=Sunset+Beach&sort=relevance');

        $response->assertOk();
        $assets = $response->viewData('page')['props']['assets']['data'];
        $this->assertSame($exact->id, $assets[0]['id']);
    }

    public function test_orientation_and_minimum_dimensions_filter_results(): void
    {
        $landscape = $this->discoverableAsset(['title' => 'Wide Landscape'], 3000, 2000);
        $portrait = $this->discoverableAsset(['title' => 'Tall Portrait'], 1600, 2400);
        app(AssetSearchDocumentService::class)->rebuild($landscape);
        app(AssetSearchDocumentService::class)->rebuild($portrait);

        $response = $this->get('/images?orientation=landscape&min_width=2500');
        $response->assertOk();
        $assets = collect($response->viewData('page')['props']['assets']['data']);
        $this->assertTrue($assets->contains('id', $landscape->id));
        $this->assertFalse($assets->contains('id', $portrait->id));
    }

    private function discoverableAsset(array $attributes, int $width = 2400, int $height = 1600): Asset
    {
        $asset = Asset::factory()->create($attributes + ['status' => 'published', 'is_active' => true, 'published_at' => now()]);
        $file = AssetFile::factory()->create(['asset_id' => $asset->id, 'role' => 'preview', 'media_type' => 'image', 'width' => $width, 'height' => $height, 'extension' => 'jpg', 'is_active' => true, 'is_downloadable' => true]);
        $license = LicenseType::query()->create(['name' => 'Search Test', 'slug' => 'search-test-'.$asset->id, 'description' => 'Test', 'price_cents' => 1000, 'currency' => 'USD', 'is_active' => true, 'sort_order' => 1]);
        $offering = AssetOffering::query()->create(['asset_id' => $asset->id, 'license_type_id' => $license->id, 'name' => 'Download', 'price_cents' => 1000, 'currency' => 'USD', 'include_all_active_files' => true, 'is_active' => true, 'sort_order' => 1]);
        return $asset->fresh();
    }
}

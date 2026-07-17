<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\AssetOffering;
use App\Models\LicenseType;
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

        $licenseType = LicenseType::query()->updateOrCreate(
            ['slug' => 'commercial'],
            [
                'name' => 'Commercial',
                'description' => 'Commercial use license.',
                'price_cents' => 2900,
                'currency' => 'USD',
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        $file = AssetFile::factory()->create([
            'asset_id' => $asset->id,
            'extension' => 'jpg',
            'is_active' => true,
            'is_downloadable' => true,
        ]);

        $offering = AssetOffering::query()->create([
            'asset_id' => $asset->id,
            'license_type_id' => $licenseType->id,
            'name' => 'Commercial JPG',
            'price_cents' => 2900,
            'currency' => 'USD',
            'include_all_active_files' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $offering->files()->attach($file->id, ['sort_order' => 1]);

        $response = $this->getJson(
            "/assets/{$asset->slug}/card-data",
        );

        $response->assertOk()
            ->assertJsonPath('asset.id', $asset->id)
            ->assertJsonPath('asset.slug', $asset->slug)
            ->assertJsonPath('asset.title', $asset->title)
            ->assertJsonPath('asset.starting_price_cents', 2900)
            ->assertJsonPath('asset.offerings_count', 1)
            ->assertJsonPath('asset.offerings.0.license_type.name', 'Commercial')
            ->assertJsonPath('asset.offerings.0.formats.0', 'JPG')
            ->assertJsonPath('asset.license_href', route('assets.show', $asset).'#purchase');
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

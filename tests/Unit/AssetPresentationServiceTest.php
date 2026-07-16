<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Services\AssetPresentationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetPresentationServiceTest extends TestCase
{
    public function test_replacing_marketplace_image_uses_a_unique_path(): void
    {
        Storage::fake('public');

        $asset = Asset::factory()->create();
        $service = app(AssetPresentationService::class);

        $first = $service->saveMarketplace(
            $asset,
            UploadedFile::fake()->image('first.jpg', 1200, 675),
            ['preset' => 'marketplace-card'],
        );

        $second = $service->saveMarketplace(
            $asset->fresh(),
            UploadedFile::fake()->image('second.jpg', 1200, 675),
            ['preset' => 'marketplace-card'],
        );

        $this->assertNotSame($first['path'], $second['path']);
        Storage::disk('public')->assertMissing($first['path']);
        Storage::disk('public')->assertExists($second['path']);
    }
}

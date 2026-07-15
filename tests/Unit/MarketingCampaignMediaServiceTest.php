<?php

namespace Tests\Unit;

use App\Services\MarketingCampaignMediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketingCampaignMediaServiceTest extends TestCase
{
    public function test_rendered_images_receive_unique_filenames(): void
    {
        Storage::fake('public');

        $service = app(MarketingCampaignMediaService::class);
        $first = $service->storeEdited(
            UploadedFile::fake()->image('first.jpg', 1920, 800),
            'marketing/campaigns/example',
        );
        $second = $service->storeEdited(
            UploadedFile::fake()->image('second.jpg', 1920, 800),
            'marketing/campaigns/example',
        );

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertExists($first);
        Storage::disk('public')->assertExists($second);
    }
}

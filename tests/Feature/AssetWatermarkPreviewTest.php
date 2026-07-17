<?php

use App\Enums\AssetFileRole;
use App\Enums\AssetMediaType;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Services\AssetWatermarkPreviewService;
use App\Services\SiteSettingService;
use Illuminate\Support\Facades\Storage;

function pngFixture(int $width, int $height, bool $transparent = false): string
{
    $image = imagecreatetruecolor($width, $height);
    imagealphablending($image, false);
    imagesavealpha($image, true);

    if ($transparent) {
        $background = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $background);
        $foreground = imagecolorallocatealpha($image, 255, 255, 255, 20);
        imagefilledellipse($image, (int) ($width / 2), (int) ($height / 2), $width - 4, $height - 4, $foreground);
    } else {
        $background = imagecolorallocate($image, 40, 80, 120);
        imagefill($image, 0, 0, $background);
    }

    ob_start();
    imagepng($image);
    $contents = ob_get_clean();
    imagedestroy($image);

    return $contents;
}

it('creates a dedicated watermarked webp preview from the branding settings', function () {
    Storage::fake('public');
    Storage::fake('asset-files');

    Storage::disk('asset-files')->put('assets/source.jpg', pngFixture(1200, 800));
    Storage::disk('public')->put('site/branding/watermark.png', pngFixture(300, 120, true));

    $settings = Mockery::mock(SiteSettingService::class);
    $settings->shouldReceive('get')->with('branding.watermark_logo')->andReturn('/storage/site/branding/watermark.png');
    $settings->shouldReceive('get')->with('branding.watermark_enabled', true)->andReturn(true);
    $settings->shouldReceive('get')->with('branding.watermark_opacity', 70)->andReturn(55);
    $settings->shouldReceive('get')->with('branding.watermark_position', 'center')->andReturn('bottom-right');
    $settings->shouldReceive('get')->with('branding.watermark_scale', 35)->andReturn(30);
    $settings->shouldReceive('get')->with('branding.watermark_margin', 24)->andReturn(20);

    $asset = new Asset(['uuid' => '11111111-1111-4111-8111-111111111111']);
    $asset->id = 9;

    $file = new AssetFile([
        'asset_id' => 9,
        'uuid' => '22222222-2222-4222-8222-222222222222',
        'role' => AssetFileRole::Preview,
        'media_type' => AssetMediaType::Image,
        'disk' => 'asset-files',
        'directory' => 'assets',
        'stored_filename' => 'source.jpg',
        'original_filename' => 'source.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'checksum_sha256' => hash('sha256', 'fixture'),
        'is_active' => true,
    ]);

    $service = new AssetWatermarkPreviewService($settings);
    $response = $service->assetFileResponse($asset, $file);

    expect($response->headers->get('content-type'))->toBe('image/webp');
    Storage::disk('public')->assertExists(
        'assets/11111111-1111-4111-8111-111111111111/watermarked/file-22222222-2222-4222-8222-222222222222.webp',
    );
    Storage::disk('public')->assertExists(
        'assets/11111111-1111-4111-8111-111111111111/watermarked/file-22222222-2222-4222-8222-222222222222.json',
    );
});

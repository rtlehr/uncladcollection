<?php

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileRole;
use App\Enums\AssetFileScanStatus;
use App\Enums\AssetMediaType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    expect(DB::connection()->getDriverName())->toBe('mysql');
    expect(DB::connection()->getDatabaseName())->toBe('uncladcollection_testing');
});

it('renders a published public asset experience', function (): void {
    $asset = publicAssetTestAsset(AssetStatus::Published);
    publicAssetTestFile($asset);

    $this->get(route('assets.show', $asset))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Assets/Show')
            ->where('asset.id', $asset->id)
            ->where('asset.title', $asset->title)
            ->has('asset.files', 1));
});

it('does not expose draft assets publicly', function (): void {
    $asset = publicAssetTestAsset(AssetStatus::Draft);
    $this->get(route('assets.show', $asset))->assertNotFound();
});

it('serves an approved private preview inline', function (): void {
    Storage::fake('asset-files');
    $asset = publicAssetTestAsset(AssetStatus::Published);
    $file = publicAssetTestFile($asset);
    Storage::disk('asset-files')->put($file->path, 'preview-content');

    $response = $this->get(route('assets.preview', [$asset, $file]));
    $response->assertOk()->assertHeader('Content-Type', 'image/jpeg');
});

function publicAssetTestAsset(AssetStatus $status): Asset
{
    return Asset::query()->create([
        'uuid' => (string) Str::uuid(),
        'title' => 'Public Asset '.Str::random(6),
        'slug' => 'public-asset-'.Str::lower(Str::random(8)),
        'asset_type' => AssetType::Image,
        'status' => $status,
        'is_active' => true,
        'published_at' => $status === AssetStatus::Published ? now()->subMinute() : null,
    ]);
}

function publicAssetTestFile(Asset $asset): AssetFile
{
    $file = AssetFile::query()->create([
        'asset_id' => $asset->id,
        'uuid' => (string) Str::uuid(),
        'role' => AssetFileRole::Preview,
        'media_type' => AssetMediaType::Image,
        'disk' => 'asset-files',
        'directory' => 'assets/testing/'.$asset->uuid.'/preview',
        'stored_filename' => Str::ulid().'.jpg',
        'original_filename' => 'preview.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1024,
        'checksum_sha256' => hash('sha256', $asset->uuid),
        'sort_order' => 10,
        'width' => 1200,
        'height' => 800,
        'processing_status' => AssetFileProcessingStatus::Ready,
        'virus_scan_status' => AssetFileScanStatus::NotRequired,
        'is_downloadable' => false,
        'is_active' => true,
        'is_legacy' => false,
    ]);

    $asset->update(['primary_preview_file_id' => $file->id]);
    return $file;
}

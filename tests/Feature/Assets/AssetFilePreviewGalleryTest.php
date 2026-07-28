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
    assertDedicatedTestDatabase();
    Storage::fake('asset-files');
});

it('includes an associated video in the public gallery even when the primary preview is an image', function (): void {
    $asset = galleryAsset();
    $image = galleryFile($asset, 'jpg', AssetMediaType::Image, AssetFileRole::Preview, 'image/jpeg');
    $video = galleryFile($asset, 'mp4', AssetMediaType::Video, AssetFileRole::Video, 'video/mp4');
    $asset->update(['primary_preview_file_id' => $image->id]);

    $this->get(route('assets.show', $asset))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Assets/Show')
            ->has('asset.files', 2)
            ->where('asset.files.1.id', $video->id)
            ->where('asset.files.1.can_preview', true)
            ->where('asset.files.1.preview_kind', 'video')
            ->where(
                'asset.files.1.preview_url',
                fn (string $url): bool => str_starts_with(
                    $url,
                    route('assets.preview', [$asset, $video])
                )
            ));
});

it('serves browser-safe video files inline', function (): void {
    $asset = galleryAsset();
    $video = galleryFile($asset, 'mp4', AssetMediaType::Video, AssetFileRole::Video, 'video/mp4');
    Storage::disk('asset-files')->put($video->path, 'video-content');

    $this->get(route('assets.preview', [$asset, $video]))
        ->assertOk()
        ->assertHeader('Content-Type', 'video/mp4')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('does not expose archives through the preview endpoint', function (): void {
    $asset = galleryAsset();
    $archive = galleryFile($asset, 'zip', AssetMediaType::Archive, AssetFileRole::Bundle, 'application/zip');
    Storage::disk('asset-files')->put($archive->path, 'archive-content');

    $this->get(route('assets.preview', [$asset, $archive]))->assertForbidden();
});

function galleryAsset(): Asset
{
    return Asset::query()->create([
        'uuid' => (string) Str::uuid(),
        'title' => 'Gallery Asset '.Str::random(5),
        'slug' => 'gallery-asset-'.Str::lower(Str::random(8)),
        'asset_type' => AssetType::MixedMedia,
        'status' => AssetStatus::Published,
        'is_active' => true,
        'published_at' => now()->subMinute(),
    ]);
}

function galleryFile(Asset $asset, string $extension, AssetMediaType $mediaType, AssetFileRole $role, string $mime): AssetFile
{
    return AssetFile::query()->create([
        'asset_id' => $asset->id,
        'uuid' => (string) Str::uuid(),
        'role' => $role,
        'media_type' => $mediaType,
        'disk' => 'asset-files',
        'directory' => 'assets/testing/'.$asset->uuid.'/'.$mediaType->value,
        'stored_filename' => Str::ulid().'.'.$extension,
        'original_filename' => 'sample.'.$extension,
        'extension' => $extension,
        'mime_type' => $mime,
        'size_bytes' => 2048,
        'checksum_sha256' => hash('sha256', $asset->uuid.$extension),
        'sort_order' => $role === AssetFileRole::Preview ? 10 : 20,
        'processing_status' => AssetFileProcessingStatus::Ready,
        'virus_scan_status' => AssetFileScanStatus::NotRequired,
        'is_downloadable' => true,
        'is_active' => true,
        'is_legacy' => false,
    ]);
}

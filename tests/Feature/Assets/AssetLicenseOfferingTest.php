<?php

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileRole;
use App\Enums\AssetFileScanStatus;
use App\Enums\AssetMediaType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\AssetOffering;
use App\Models\LicenseType;
use App\Services\AssetOfferingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    expect(DB::connection()->getDriverName())->toBe('mysql');
    expect(DB::connection()->getDatabaseName())->toBe('uncladcollection_testing');
});

it('creates asset-specific offerings with selected files', function (): void {
    $asset = createOfferingTestAsset('Commercial Beach Package');
    $licenseType = createOfferingTestLicenseType('Commercial', 'commercial', 2500);
    $file = createOfferingTestFile($asset, 'beach-high-resolution.jpg', 10);

    app(AssetOfferingService::class)->saveMany($asset, [[
        'license_type_id' => $licenseType->id,
        'name' => 'Commercial License',
        'price_cents' => 3500,
        'currency' => 'USD',
        'download_limit' => 5,
        'expires_after_days' => null,
        'include_all_active_files' => false,
        'is_active' => true,
        'file_ids' => [$file->id],
    ]]);

    $offering = AssetOffering::query()->firstOrFail();

    expect($offering->asset_id)->toBe($asset->id)
        ->and($offering->license_type_id)->toBe($licenseType->id)
        ->and($offering->price_cents)->toBe(3500)
        ->and($offering->download_limit)->toBe(5)
        ->and($offering->include_all_active_files)->toBeFalse()
        ->and($offering->files()->pluck('asset_files.id')->all())->toBe([$file->id]);

    $this->assertDatabaseHas('asset_offerings', [
        'id' => $offering->id,
        'asset_id' => $asset->id,
        'license_type_id' => $licenseType->id,
        'price_cents' => 3500,
        'download_limit' => 5,
        'include_all_active_files' => 0,
        'is_active' => 1,
    ]);

    $this->assertDatabaseHas('asset_offering_files', [
        'asset_offering_id' => $offering->id,
        'asset_file_id' => $file->id,
    ]);
});

it('includes future active downloadable files for complete package offerings', function (): void {
    $asset = createOfferingTestAsset('Complete Beach Package');
    $licenseType = createOfferingTestLicenseType('Complete', 'complete', 5000);

    $offering = AssetOffering::query()->create([
        'asset_id' => $asset->id,
        'license_type_id' => $licenseType->id,
        'name' => 'Complete Package',
        'price_cents' => 5000,
        'currency' => 'USD',
        'include_all_active_files' => true,
        'is_active' => true,
    ]);

    $firstFile = createOfferingTestFile($asset, 'complete-package.jpg', 10);
    $secondFile = createOfferingTestFile(
        $asset,
        'complete-package.eps',
        20,
        AssetFileRole::Vector,
        AssetMediaType::Vector,
        'eps',
        'application/postscript',
    );

    $excludedInactiveFile = createOfferingTestFile($asset, 'inactive-file.jpg', 30, isActive: false);
    $excludedNonDownloadableFile = createOfferingTestFile($asset, 'preview-only.jpg', 40, isDownloadable: false);

    $includedFileIds = $offering->includedFiles()->pluck('id')->all();

    expect($includedFileIds)->toHaveCount(2)
        ->and($includedFileIds)->toContain($firstFile->id, $secondFile->id)
        ->and($includedFileIds)->not->toContain($excludedInactiveFile->id, $excludedNonDownloadableFile->id);
});

function createOfferingTestAsset(string $title): Asset
{
    return Asset::query()->create([
        'uuid' => (string) Str::uuid(),
        'title' => $title,
        'slug' => Str::slug($title).'-'.Str::lower(Str::random(8)),
        'asset_type' => AssetType::MixedMedia,
        'status' => AssetStatus::Draft,
        'is_active' => true,
    ]);
}

function createOfferingTestLicenseType(string $name, string $slug, int $priceCents): LicenseType
{
    return LicenseType::query()->create([
        'name' => $name,
        'slug' => $slug.'-'.Str::lower(Str::random(8)),
        'description' => $name.' license used by the asset-offering feature test.',
        'price_cents' => $priceCents,
        'currency' => 'USD',
        'max_resolution' => 'high_res',
        'download_limit' => 5,
        'is_active' => true,
    ]);
}

function createOfferingTestFile(
    Asset $asset,
    string $originalFilename,
    int $sortOrder,
    AssetFileRole $role = AssetFileRole::HighResolution,
    AssetMediaType $mediaType = AssetMediaType::Image,
    string $extension = 'jpg',
    string $mimeType = 'image/jpeg',
    bool $isActive = true,
    bool $isDownloadable = true,
): AssetFile {
    return AssetFile::query()->create([
        'asset_id' => $asset->id,
        'uuid' => (string) Str::uuid(),
        'role' => $role,
        'media_type' => $mediaType,
        'disk' => 'asset-files',
        'directory' => 'assets/2026/07/testing/'.$asset->uuid.'/'.$mediaType->value,
        'stored_filename' => (string) Str::ulid().'.'.$extension,
        'original_filename' => $originalFilename,
        'extension' => $extension,
        'mime_type' => $mimeType,
        'size_bytes' => 1024,
        'checksum_sha256' => hash('sha256', $asset->uuid.$originalFilename),
        'sort_order' => $sortOrder,
        'processing_status' => AssetFileProcessingStatus::Ready,
        'virus_scan_status' => AssetFileScanStatus::NotRequired,
        'is_downloadable' => $isDownloadable,
        'is_active' => $isActive,
        'is_legacy' => false,
    ]);
}

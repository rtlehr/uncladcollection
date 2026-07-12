<?php

use App\Enums\AssetFileRole;
use App\Enums\AssetMediaType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    expect(DB::connection()->getDriverName())->toBe('mysql');
    expect(DB::connection()->getDatabaseName())->toBe('uncladcollection_testing');
});

it('creates a backward-compatible asset for a legacy image', function (): void {
    $image = Image::query()->create([
        'title' => 'Sunset Beach Couple',
        'slug' => 'sunset-beach-couple-'.Str::lower(Str::random(8)),
        'description' => 'Legacy image listing.',
        'thumbnail_path' => 'images/test/thumbnail/sunset.jpg',
        'is_active' => true,
    ]);

    $asset = Asset::query()->create([
        'uuid' => (string) Str::uuid(),
        'legacy_image_id' => $image->id,
        'title' => $image->title,
        'slug' => $image->slug,
        'asset_type' => AssetType::Image,
        'status' => AssetStatus::Published,
        'is_active' => true,
    ]);

    $image->refresh();
    $asset->refresh();

    expect($image->asset)->not->toBeNull()
        ->and($image->asset->is($asset))->toBeTrue()
        ->and($asset->legacyImage)->not->toBeNull()
        ->and($asset->legacyImage->is($image))->toBeTrue();

    $this->assertDatabaseHas('assets', [
        'id' => $asset->id,
        'legacy_image_id' => $image->id,
        'asset_type' => AssetType::Image->value,
        'status' => AssetStatus::Published->value,
    ]);
});

it('supports multiple files with independent roles on one asset', function (): void {
    $asset = Asset::query()->create([
        'uuid' => (string) Str::uuid(),
        'title' => 'Complete Beach Package',
        'slug' => 'complete-beach-package-'.Str::lower(Str::random(8)),
        'asset_type' => AssetType::MixedMedia,
        'status' => AssetStatus::Draft,
    ]);

    $imageFile = AssetFile::query()->create([
        'asset_id' => $asset->id,
        'uuid' => (string) Str::uuid(),
        'role' => AssetFileRole::HighResolution,
        'media_type' => AssetMediaType::Image,
        'disk' => 'asset-files',
        'directory' => 'assets/2026/07/test/image',
        'stored_filename' => Str::uuid().'.jpg',
        'original_filename' => 'beach.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'sort_order' => 10,
    ]);

    $vectorFile = AssetFile::query()->create([
        'asset_id' => $asset->id,
        'uuid' => (string) Str::uuid(),
        'role' => AssetFileRole::Vector,
        'media_type' => AssetMediaType::Vector,
        'disk' => 'asset-files',
        'directory' => 'assets/2026/07/test/vector',
        'stored_filename' => Str::uuid().'.eps',
        'original_filename' => 'beach.eps',
        'extension' => 'eps',
        'mime_type' => 'application/postscript',
        'sort_order' => 20,
    ]);

    $asset->refresh();

    expect($asset->files)->toHaveCount(2)
        ->and($asset->files->pluck('id')->all())->toContain($imageFile->id, $vectorFile->id)
        ->and($asset->files->pluck('role')->all())->toContain(
            AssetFileRole::HighResolution,
            AssetFileRole::Vector,
        );

    $this->assertDatabaseHas('asset_files', [
        'id' => $imageFile->id,
        'asset_id' => $asset->id,
        'role' => AssetFileRole::HighResolution->value,
        'media_type' => AssetMediaType::Image->value,
    ]);

    $this->assertDatabaseHas('asset_files', [
        'id' => $vectorFile->id,
        'asset_id' => $asset->id,
        'role' => AssetFileRole::Vector->value,
        'media_type' => AssetMediaType::Vector->value,
    ]);
});

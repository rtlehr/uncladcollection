<?php

use App\Enums\AssetFileRole;
use App\Enums\AssetMediaType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a backward-compatible asset for a legacy image', function () {
    $image = Image::query()->create([
        'title' => 'Sunset Beach Couple',
        'slug' => 'sunset-beach-couple',
        'description' => 'Legacy image listing.',
        'thumbnail_path' => 'images/test/thumbnail/sunset.jpg',
        'is_active' => true,
    ]);

    $asset = Asset::query()->create([
        'uuid' => fake()->uuid(),
        'legacy_image_id' => $image->id,
        'title' => $image->title,
        'slug' => $image->slug,
        'asset_type' => AssetType::Image,
        'status' => AssetStatus::Published,
        'is_active' => true,
    ]);

    expect($image->fresh()->asset->is($asset))->toBeTrue()
        ->and($asset->legacyImage->is($image))->toBeTrue();
});

it('supports multiple files with independent roles on one asset', function () {
    $asset = Asset::query()->create([
        'uuid' => fake()->uuid(),
        'title' => 'Complete Beach Package',
        'slug' => 'complete-beach-package',
        'asset_type' => AssetType::MixedMedia,
        'status' => AssetStatus::Draft,
    ]);

    AssetFile::query()->create([
        'asset_id' => $asset->id,
        'uuid' => fake()->uuid(),
        'role' => AssetFileRole::HighResolution,
        'media_type' => AssetMediaType::Image,
        'disk' => 'asset-files',
        'directory' => 'assets/2026/07/test/image',
        'stored_filename' => 'image.jpg',
        'original_filename' => 'beach.jpg',
        'extension' => 'jpg',
    ]);

    AssetFile::query()->create([
        'asset_id' => $asset->id,
        'uuid' => fake()->uuid(),
        'role' => AssetFileRole::Vector,
        'media_type' => AssetMediaType::Vector,
        'disk' => 'asset-files',
        'directory' => 'assets/2026/07/test/vector',
        'stored_filename' => 'vector.eps',
        'original_filename' => 'beach.eps',
        'extension' => 'eps',
    ]);

    expect($asset->fresh()->files)->toHaveCount(2)
        ->and($asset->files->pluck('role')->all())->toContain(
            AssetFileRole::HighResolution,
            AssetFileRole::Vector,
        );
});

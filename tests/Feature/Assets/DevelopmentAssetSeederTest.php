<?php

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\AssetOffering;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\DevelopmentAssetSeeder;
use Database\Seeders\LicenseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    assertDedicatedTestDatabase();

    Storage::fake('asset-files');

    $this->seed(CollectionSeeder::class);
    $this->seed(LicenseTypeSeeder::class);
});

it('creates a representative asset-native development catalog with real fixture files', function (): void {
    $this->seed(DevelopmentAssetSeeder::class);

    expect(Asset::query()->count())->toBe(5)
        ->and(AssetFile::query()->count())->toBe(14)
        ->and(AssetOffering::query()->count())->toBe(15)
        ->and(Asset::query()->where('status', AssetStatus::Published)->count())->toBe(5)
        ->and(Asset::query()->where('is_featured', true)->count())->toBe(4);

    $types = Asset::query()
    ->orderBy('asset_type')
    ->get()
    ->pluck('asset_type')
    ->map(fn ($type) => $type instanceof \BackedEnum ? $type->value : $type)
    ->unique()
    ->values()
    ->all();

expect($types)->toContain(
    'image',
    'vector',
    'video',
    'mixed_media',
    'document',
);

    AssetFile::query()->each(function (AssetFile $file): void {
        Storage::disk($file->disk)->assertExists($file->path);

        expect($file->uuid)->not->toBeEmpty()
            ->and($file->checksum_sha256)->toHaveLength(64)
            ->and($file->is_legacy)->toBeFalse();
    });

    Asset::query()->each(function (Asset $asset): void {
        expect($asset->uuid)->not->toBeEmpty()
            ->and($asset->primary_preview_file_id)->not->toBeNull()
            ->and($asset->offerings()->count())->toBe(3)
            ->and($asset->metadata['development_seed'] ?? false)->toBeTrue();
    });
});

it('can be rerun without duplicating development records', function (): void {
    $this->seed(DevelopmentAssetSeeder::class);
    $this->seed(DevelopmentAssetSeeder::class);

    expect(Asset::query()->count())->toBe(5)
        ->and(AssetFile::query()->count())->toBe(14)
        ->and(AssetOffering::query()->count())->toBe(15);
});

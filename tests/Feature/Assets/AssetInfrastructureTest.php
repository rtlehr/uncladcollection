<?php

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileRole;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Jobs\ProcessAssetFile;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Services\AssetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    expect(DB::connection()->getDriverName())->toBe('mysql');
    expect(DB::connection()->getDatabaseName())->toBe('uncladcollection_testing');

    Storage::fake('asset-files');
    Queue::fake();
});

it('stores a validated asset file privately and queues processing', function (): void {
    $asset = createInfrastructureTestAsset('Beach Package', AssetType::MixedMedia);

    $file = UploadedFile::fake()->image('beach.jpg', 1200, 800);
    $assetFile = app(AssetService::class)->addFile(
        $asset,
        $file,
        AssetFileRole::HighResolution,
    );

    expect($assetFile->disk)->toBe('asset-files')
        ->and($assetFile->role)->toBe(AssetFileRole::HighResolution)
        ->and($assetFile->processing_status)->toBe(AssetFileProcessingStatus::Pending)
        ->and($assetFile->checksum_sha256)->toHaveLength(64)
        ->and($assetFile->is_legacy)->toBeFalse();

    $this->assertDatabaseHas('asset_files', [
        'id' => $assetFile->id,
        'asset_id' => $asset->id,
        'disk' => 'asset-files',
        'role' => AssetFileRole::HighResolution->value,
        'processing_status' => AssetFileProcessingStatus::Pending->value,
        'is_legacy' => 0,
    ]);

    Storage::disk('asset-files')->assertExists($assetFile->path);
    Queue::assertPushed(
        ProcessAssetFile::class,
        fn (ProcessAssetFile $job): bool => $job->assetFileId === $assetFile->id,
    );
});

it('keeps the physical file when an asset file is soft deleted', function (): void {
    $asset = createInfrastructureTestAsset('Protected Purchase File', AssetType::Image);

    $assetFile = app(AssetService::class)->addFile(
        $asset,
        UploadedFile::fake()->image('protected.jpg'),
        process: false,
    );

    $path = $assetFile->path;
    $assetFileId = $assetFile->id;

    app(AssetService::class)->removeFile($assetFile);

    expect(AssetFile::query()->find($assetFileId))->toBeNull();

    $deletedFile = AssetFile::withTrashed()->findOrFail($assetFileId);

    expect($deletedFile->trashed())->toBeTrue()
        ->and($deletedFile->is_active)->toBeFalse();

    $this->assertSoftDeleted('asset_files', ['id' => $assetFileId]);
    Storage::disk('asset-files')->assertExists($path);
});

it('replaces a file without deleting the previous physical revision', function (): void {
    $asset = createInfrastructureTestAsset('Revisioned Asset', AssetType::Image);
    $service = app(AssetService::class);

    $original = $service->addFile(
        $asset,
        UploadedFile::fake()->image('original.jpg'),
        process: false,
    );

    $originalPath = $original->path;

    $replacement = $service->replaceFile(
        $original,
        UploadedFile::fake()->image('replacement.jpg'),
        process: false,
    );

    $previousRevision = AssetFile::withTrashed()->findOrFail($original->id);

    expect($replacement->id)->not->toBe($original->id)
        ->and($replacement->metadata['replaces_asset_file_id'])->toBe($original->id)
        ->and($previousRevision->trashed())->toBeTrue()
        ->and($previousRevision->is_active)->toBeFalse();

    $this->assertDatabaseHas('asset_files', [
        'id' => $replacement->id,
        'asset_id' => $asset->id,
        'is_active' => 1,
    ]);

    $this->assertSoftDeleted('asset_files', ['id' => $original->id]);
    Storage::disk('asset-files')->assertExists($originalPath);
    Storage::disk('asset-files')->assertExists($replacement->path);
});

function createInfrastructureTestAsset(string $title, AssetType $type): Asset
{
    return Asset::query()->create([
        'uuid' => (string) Str::uuid(),
        'title' => $title,
        'slug' => Str::slug($title).'-'.Str::lower(Str::random(8)),
        'asset_type' => $type,
        'status' => AssetStatus::Draft,
        'is_active' => true,
    ]);
}

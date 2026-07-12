<?php

use App\Enums\AssetFileRole;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
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

it('stores multiple independently classified files on one asset', function (): void {
    $asset = createMultiFileTestAsset('Mixed Beach Package');
    $service = app(AssetService::class);

    $preview = $service->addFile(
        $asset,
        UploadedFile::fake()->image('beach-preview.jpg', 1200, 800),
        AssetFileRole::Preview,
        process: false,
        attributes: ['sort_order' => 10, 'is_downloadable' => false],
    );

    $zipUpload = createRealZipUpload('beach-source.zip');

    try {
        $source = $service->addFile(
            $asset,
            $zipUpload,
            AssetFileRole::Bundle,
            process: false,
            attributes: ['sort_order' => 20, 'is_downloadable' => true],
        );
    } finally {
        @unlink($zipUpload->getPathname());
    }

    $service->setPrimaryPreview($asset, $preview);

    expect($asset->files()->count())->toBe(2)
        ->and($asset->fresh()->primary_preview_file_id)->toBe($preview->id)
        ->and($preview->is_downloadable)->toBeFalse()
        ->and($source->is_downloadable)->toBeTrue();

    $this->assertDatabaseHas('asset_files', [
        'asset_id' => $asset->id,
        'role' => AssetFileRole::Preview->value,
        'sort_order' => 10,
    ]);

    $this->assertDatabaseHas('asset_files', [
        'asset_id' => $asset->id,
        'role' => AssetFileRole::Bundle->value,
        'sort_order' => 20,
    ]);
});

it('updates file roles and ordering without replacing the stored file', function (): void {
    $asset = createMultiFileTestAsset('Ordered Package');
    $service = app(AssetService::class);

    $first = $service->addFile($asset, UploadedFile::fake()->image('first.jpg'), AssetFileRole::Primary, false, ['sort_order' => 10]);
    $second = $service->addFile($asset, UploadedFile::fake()->image('second.jpg'), AssetFileRole::Supplemental, false, ['sort_order' => 20]);

    $firstPath = $first->path;

    $first->update(['role' => AssetFileRole::HighResolution, 'sort_order' => 20]);
    $second->update(['sort_order' => 10]);

    expect($asset->files()->first()->id)->toBe($second->id)
        ->and($first->fresh()->role)->toBe(AssetFileRole::HighResolution);

    Storage::disk('asset-files')->assertExists($firstPath);
});

it('removes a file record while retaining its physical revision', function (): void {
    $asset = createMultiFileTestAsset('Protected Revision');
    $file = app(AssetService::class)->addFile(
        $asset,
        UploadedFile::fake()->image('protected.jpg'),
        process: false,
    );

    $path = $file->path;
    app(AssetService::class)->removeFile($file);

    expect(AssetFile::query()->find($file->id))->toBeNull();
    $this->assertSoftDeleted('asset_files', ['id' => $file->id]);
    Storage::disk('asset-files')->assertExists($path);
});

function createMultiFileTestAsset(string $title): Asset
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

function createRealZipUpload(string $originalName): UploadedFile
{
    if (! class_exists(ZipArchive::class)) {
        test()->markTestSkipped('The PHP zip extension is required for ZIP upload tests.');
    }

    $path = tempnam(sys_get_temp_dir(), 'uc-asset-zip-');

    if ($path === false) {
        throw new RuntimeException('Unable to create a temporary ZIP test file.');
    }

    $zip = new ZipArchive();
    $opened = $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    if ($opened !== true) {
        @unlink($path);
        throw new RuntimeException('Unable to create the ZIP test fixture.');
    }

    $zip->addFromString('README.txt', 'Unclad Collection asset bundle test fixture.');
    $zip->close();

    return new UploadedFile(
        path: $path,
        originalName: $originalName,
        mimeType: 'application/zip',
        error: UPLOAD_ERR_OK,
        test: true,
    );
}

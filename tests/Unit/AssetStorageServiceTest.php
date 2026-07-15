<?php

namespace Tests\Unit;

use App\Enums\AssetFileRole;
use App\Models\Asset;
use App\Services\AssetStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetStorageServiceTest extends TestCase
{
    public function test_checksum_supports_fake_file_with_reported_size(): void
    {
        $file = UploadedFile::fake()->create(
            'source.zip',
            250,
            'application/zip',
        );

        $checksum = app(AssetStorageService::class)->checksum($file);

        $this->assertSame(hash('sha256', ''), $checksum);
    }

    public function test_checksum_supports_fake_file_with_content(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'source.zip',
            'archive bytes',
        );

        $checksum = app(AssetStorageService::class)->checksum($file);

        $this->assertSame(
            hash('sha256', 'archive bytes'),
            $checksum,
        );
    }

    public function test_storage_supports_fake_archive_and_image(): void
    {
        Storage::fake('asset-files');
        config()->set('asset-media.private_disk', 'asset-files');

        $asset = Asset::factory()->create();

        $archive = app(AssetStorageService::class)->store(
            $asset,
            UploadedFile::fake()->create(
                'source.zip',
                250,
                'application/zip',
            ),
            AssetFileRole::Bundle,
        );

        $image = app(AssetStorageService::class)->store(
            $asset,
            UploadedFile::fake()->image(
                'preview.jpg',
                1200,
                800,
            ),
            AssetFileRole::Preview,
        );

        Storage::disk('asset-files')->assertExists($archive['path']);
        Storage::disk('asset-files')->assertExists($image['path']);

        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $archive['checksum_sha256'],
        );
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $image['checksum_sha256'],
        );
    }
}

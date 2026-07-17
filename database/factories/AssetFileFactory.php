<?php

namespace Database\Factories;

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileRole;
use App\Enums\AssetFileScanStatus;
use App\Enums\AssetMediaType;
use App\Models\Asset;
use App\Models\AssetFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AssetFile>
 */
class AssetFileFactory extends Factory
{
    protected $model = AssetFile::class;

    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'asset_id' => Asset::factory(),
            'uuid' => $uuid,
            'role' => AssetFileRole::Primary,
            'media_type' => AssetMediaType::Image,
            'disk' => 'public',
            'directory' => 'assets/testing/'.$uuid,
            'stored_filename' => 'test-image.jpg',
            'original_filename' => 'test-image.jpg',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'checksum_sha256' => hash('sha256', $uuid),
            'sort_order' => 0,
            'width' => 1200,
            'height' => 800,
            'duration_seconds' => null,
            'page_count' => null,
            'metadata' => [],
            'processing_status' => AssetFileProcessingStatus::Ready,
            'virus_scan_status' => AssetFileScanStatus::Clean,
            'is_downloadable' => true,
            'is_active' => true,
            'is_legacy' => false,
        ];
    }

    public function preview(): static
    {
        return $this->state(fn () => [
            'role' => AssetFileRole::Preview,
            'is_downloadable' => false,
        ]);
    }

    public function vector(): static
    {
        return $this->state(fn () => [
            'role' => AssetFileRole::Vector,
            'media_type' => AssetMediaType::Vector,
            'stored_filename' => 'test-vector.svg',
            'original_filename' => 'test-vector.svg',
            'extension' => 'svg',
            'mime_type' => 'image/svg+xml',
            'width' => null,
            'height' => null,
        ]);
    }

    public function video(): static
    {
        return $this->state(fn () => [
            'role' => AssetFileRole::Video,
            'media_type' => AssetMediaType::Video,
            'stored_filename' => 'test-video.mp4',
            'original_filename' => 'test-video.mp4',
            'extension' => 'mp4',
            'mime_type' => 'video/mp4',
            'width' => 1920,
            'height' => 1080,
            'duration_seconds' => 10.000,
        ]);
    }
}

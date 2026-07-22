<?php

namespace Database\Seeders;

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileRole;
use App\Enums\AssetFileScanStatus;
use App\Enums\AssetMediaType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\AssetOffering;
use App\Models\Collection;
use App\Models\LicenseType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DevelopmentAssetSeeder extends Seeder
{
    private const DISK = 'asset-files';

    public function run(): void
    {
        $this->guardEnvironment();

        $collections = Collection::query()->get()->keyBy('slug');
        $licenseTypes = LicenseType::query()->get()->keyBy('slug');

        foreach ($this->assetDefinitions() as $definition) {
            DB::transaction(function () use ($definition, $collections, $licenseTypes): void {
                $this->seedAsset($definition, $collections, $licenseTypes);
            });
        }
    }

    private function seedAsset(array $definition, $collections, $licenseTypes): void
    {
        $asset = Asset::withTrashed()->firstOrNew(['slug' => $definition['slug']]);

        if ($asset->trashed()) {
            $asset->restore();
        }

        $asset->fill([
            'uuid' => $asset->uuid ?: (string) Str::uuid(),
            'collection_id' => $collections->get($definition['collection'])?->id,
            'title' => $definition['title'],
            'description' => $definition['description'],
            'asset_type' => $definition['asset_type'],
            'status' => AssetStatus::Published,
            'photographer' => $definition['photographer'],
            'sort_order' => $definition['sort_order'],
            'is_active' => true,
            'is_featured' => $definition['is_featured'],
            'is_ai_generated' => $definition['is_ai_generated'],
            'published_at' => now()->subDays($definition['published_days_ago']),
            'metadata' => [
                'development_seed' => true,
                'seed_version' => 'UC-A004.6',
            ],
        ]);

        $asset->save();

        Storage::disk(self::DISK)->deleteDirectory("assets/development/{$asset->slug}");
        $asset->files()->withTrashed()->forceDelete();
        $asset->offerings()->withTrashed()->forceDelete();

        $filesByKey = collect();

        foreach ($definition['files'] as $index => $fileDefinition) {
            $filesByKey->put(
                $fileDefinition['key'],
                $this->createFile($asset, $fileDefinition, ($index + 1) * 10),
            );
        }

        $asset->update([
            'primary_preview_file_id' => $filesByKey->get($definition['primary_preview'])?->id,
            'poster_file_id' => isset($definition['poster'])
                ? $filesByKey->get($definition['poster'])?->id
                : null,
        ]);

        foreach ($definition['offerings'] as $index => $offeringDefinition) {
            $licenseType = $licenseTypes->get($offeringDefinition['license_type']);

            if (! $licenseType) {
                throw new RuntimeException("Missing license type [{$offeringDefinition['license_type']}]. Run LicenseTypeSeeder first.");
            }

            $offering = AssetOffering::create([
                'asset_id' => $asset->id,
                'license_type_id' => $licenseType->id,
                'name' => $offeringDefinition['name'],
                'description' => $offeringDefinition['description'],
                'image_units' => $offeringDefinition['image_units'],
                'video_units' => $offeringDefinition['video_units'],
                'price_cents' => $offeringDefinition['price_cents'],
                'price_adjustment_cents' => $offeringDefinition['price_adjustment_cents'] ?? 0,
                'price_override_cents' => $offeringDefinition['price_override_cents'] ?? null,
                'currency' => 'USD',
                'download_limit' => $offeringDefinition['download_limit'],
                'expires_after_days' => null,
                'include_all_active_files' => $offeringDefinition['include_all_active_files'] ?? false,
                'is_active' => true,
                'sort_order' => ($index + 1) * 10,
                'metadata' => ['development_seed' => true],
            ]);

            if (! $offering->include_all_active_files) {
                $sync = collect($offeringDefinition['files'])
                    ->values()
                    ->mapWithKeys(fn (string $key, int $position): array => [
                        $filesByKey->get($key)->id => ['sort_order' => ($position + 1) * 10],
                    ])
                    ->all();

                $offering->files()->sync($sync);
            }
        }
    }

    private function createFile(Asset $asset, array $definition, int $sortOrder): AssetFile
    {
        $fixturePath = database_path('seeders/assets/'.$definition['fixture']);

        if (! is_file($fixturePath)) {
            throw new RuntimeException("Development asset fixture is missing: {$fixturePath}");
        }

        $directory = "assets/development/{$asset->slug}/{$definition['role']->value}";
        $storedFilename = Str::ulid().'.'.$definition['extension'];
        $storagePath = $directory.'/'.$storedFilename;

        Storage::disk(self::DISK)->put($storagePath, file_get_contents($fixturePath));

        return AssetFile::create([
            'uuid' => (string) Str::uuid(),
            'asset_id' => $asset->id,
            'role' => $definition['role'],
            'media_type' => $definition['media_type'],
            'disk' => self::DISK,
            'directory' => $directory,
            'stored_filename' => $storedFilename,
            'original_filename' => $definition['original_filename'],
            'extension' => $definition['extension'],
            'mime_type' => $definition['mime_type'],
            'size_bytes' => filesize($fixturePath),
            'checksum_sha256' => hash_file('sha256', $fixturePath),
            'sort_order' => $sortOrder,
            'width' => $definition['width'] ?? null,
            'height' => $definition['height'] ?? null,
            'duration_seconds' => $definition['duration_seconds'] ?? null,
            'page_count' => $definition['page_count'] ?? null,
            'metadata' => [
                'development_seed' => true,
                ...($definition['metadata'] ?? []),
            ],
            'processing_status' => AssetFileProcessingStatus::Ready,
            'virus_scan_status' => AssetFileScanStatus::Clean,
            'is_downloadable' => $definition['is_downloadable'] ?? true,
            'is_active' => true,
            'is_legacy' => false,
        ]);
    }

    private function guardEnvironment(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DevelopmentAssetSeeder cannot run in production.');
        }
    }

    private function assetDefinitions(): array
    {
        return [
            [
                'slug' => 'coastal-morning-photography',
                'title' => 'Coastal Morning Photography',
                'description' => 'A polished coastal lifestyle image package with web, high-resolution, and print-ready versions.',
                'asset_type' => AssetType::Image,
                'collection' => 'beach-photography',
                'photographer' => 'Unclad Collection Studio',
                'sort_order' => 10,
                'is_featured' => true,
                'is_ai_generated' => false,
                'published_days_ago' => 20,
                'primary_preview' => 'preview',
                'files' => [
                    $this->file('preview', 'coastal-morning-preview.jpg', 'coastal-morning-preview.jpg', 'jpg', 'image/jpeg', AssetFileRole::Preview, AssetMediaType::Image, 1200, 800, false),
                    $this->file('highres', 'coastal-morning-highres.jpg', 'coastal-morning-high-resolution.jpg', 'jpg', 'image/jpeg', AssetFileRole::HighResolution, AssetMediaType::Image, 2400, 1600),
                    $this->file('print', 'coastal-morning-print.tiff', 'coastal-morning-print.tiff', 'tiff', 'image/tiff', AssetFileRole::Print, AssetMediaType::Image, 1800, 1200),
                ],
                'offerings' => [
                    $this->offering('personal-use', 'Personal License', 'High-resolution JPG for personal projects and personal printing.', 999, 5, ['highres']),
                    $this->offering('commercial-use', 'Commercial License', 'High-resolution JPG and print-ready TIFF for commercial projects.', 2999, 10, ['highres', 'print']),
                    $this->offering('extended-commercial-use', 'Complete Image Package', 'All active downloadable files in the image package.', 6999, null, [], true),
                ],
            ],
            [
                'slug' => 'horizon-vector-set',
                'title' => 'Horizon Vector Set',
                'description' => 'A clean horizon design supplied as a browser-ready preview plus editable SVG and EPS vector files.',
                'asset_type' => AssetType::Vector,
                'collection' => 'studio-work',
                'photographer' => 'Unclad Design Studio',
                'sort_order' => 20,
                'is_featured' => true,
                'is_ai_generated' => false,
                'published_days_ago' => 16,
                'primary_preview' => 'preview',
                'files' => [
                    $this->file('preview', 'horizon-vector-preview.png', 'horizon-vector-preview.png', 'png', 'image/png', AssetFileRole::Preview, AssetMediaType::Image, 1200, 800, false),
                    $this->file('svg', 'horizon-vector.svg', 'horizon-vector.svg', 'svg', 'image/svg+xml', AssetFileRole::Vector, AssetMediaType::Vector),
                    $this->file('eps', 'horizon-vector.eps', 'horizon-vector.eps', 'eps', 'application/postscript', AssetFileRole::Vector, AssetMediaType::Vector),
                ],
                'offerings' => [
                    $this->offering('personal-use', 'Personal Vector License', 'Editable SVG for personal creative work.', 1499, 5, ['svg']),
                    $this->offering('commercial-use', 'Commercial Vector License', 'Editable SVG and EPS source files.', 3999, 10, ['svg', 'eps']),
                    $this->offering('extended-commercial-use', 'Complete Vector Package', 'All active downloadable vector files.', 7999, null, [], true),
                ],
            ],
            [
                'slug' => 'lifestyle-motion-video',
                'title' => 'Lifestyle Motion Video',
                'description' => 'A short motion asset with a dedicated poster image and a web-compatible MP4 deliverable.',
                'asset_type' => AssetType::Video,
                'collection' => 'outdoor-lifestyle',
                'photographer' => 'Unclad Motion Studio',
                'sort_order' => 30,
                'is_featured' => true,
                'is_ai_generated' => false,
                'published_days_ago' => 12,
                'primary_preview' => 'poster',
                'poster' => 'poster',
                'files' => [
                    $this->file('poster', 'lifestyle-video-poster.jpg', 'lifestyle-video-poster.jpg', 'jpg', 'image/jpeg', AssetFileRole::Poster, AssetMediaType::Image, 1280, 720, false),
                    $this->file('video', 'lifestyle-motion.mp4', 'lifestyle-motion.mp4', 'mp4', 'video/mp4', AssetFileRole::Video, AssetMediaType::Video, 1280, 720, true, 3.0),
                ],
                'offerings' => [
                    $this->offering('personal-use', 'Personal Video License', 'MP4 video for personal projects.', 1999, 5, ['video'], false, 0, 1),
                    $this->offering('commercial-use', 'Commercial Video License', 'MP4 video for business, marketing, and social media use.', 5999, 10, ['video'], false, 0, 1),
                    $this->offering('extended-commercial-use', 'Extended Video License', 'MP4 video with extended commercial usage rights.', 11999, null, ['video'], false, 0, 1),
                ],
            ],
            [
                'slug' => 'beach-story-mixed-media',
                'title' => 'Beach Story Mixed-Media Collection',
                'description' => 'A flexible creative package containing an image, vector overlay, short MP4 teaser, and downloadable ZIP source bundle.',
                'asset_type' => AssetType::MixedMedia,
                'collection' => 'featured-collection',
                'photographer' => 'Unclad Creative Team',
                'sort_order' => 40,
                'is_featured' => true,
                'is_ai_generated' => true,
                'published_days_ago' => 8,
                'primary_preview' => 'preview',
                'poster' => 'preview',
                'files' => [
                    $this->file('preview', 'mixed-media-preview.jpg', 'beach-story-preview.jpg', 'jpg', 'image/jpeg', AssetFileRole::Preview, AssetMediaType::Image, 1200, 800, false),
                    $this->file('overlay', 'mixed-media-overlay.svg', 'beach-story-overlay.svg', 'svg', 'image/svg+xml', AssetFileRole::Vector, AssetMediaType::Vector),
                    $this->file('video', 'mixed-media-teaser.mp4', 'beach-story-teaser.mp4', 'mp4', 'video/mp4', AssetFileRole::Video, AssetMediaType::Video, 1280, 720, true, 2.0),
                    $this->file('bundle', 'mixed-media-source.zip', 'beach-story-source-package.zip', 'zip', 'application/zip', AssetFileRole::Bundle, AssetMediaType::Archive),
                ],
                'offerings' => [
                    $this->offering('personal-use', 'Personal Mixed-Media License', 'Vector overlay and MP4 teaser for personal projects.', 2998, 5, ['overlay', 'video'], false, 1, 1),
                    $this->offering('commercial-use', 'Commercial Mixed-Media License', 'Vector, video, and source ZIP for commercial projects.', 8998, 10, ['overlay', 'video', 'bundle'], false, 1, 1),
                    $this->offering('extended-commercial-use', 'Complete Creative Package', 'All active downloadable files, including future additions.', 18998, null, [], true, 1, 1),
                ],
            ],
            [
                'slug' => 'creative-asset-guide',
                'title' => 'Creative Asset Guide',
                'description' => 'A sample document asset with a visual cover and downloadable PDF reference guide.',
                'asset_type' => AssetType::Document,
                'collection' => 'studio-work',
                'photographer' => 'Unclad Editorial Team',
                'sort_order' => 50,
                'is_featured' => false,
                'is_ai_generated' => false,
                'published_days_ago' => 4,
                'primary_preview' => 'cover',
                'files' => [
                    $this->file('cover', 'creative-guide-cover.jpg', 'creative-asset-guide-cover.jpg', 'jpg', 'image/jpeg', AssetFileRole::Preview, AssetMediaType::Image, 1000, 1300, false),
                    $this->file('pdf', 'creative-asset-guide.pdf', 'creative-asset-guide.pdf', 'pdf', 'application/pdf', AssetFileRole::Primary, AssetMediaType::Document, null, null, true, null, 1),
                ],
                'offerings' => [
                    $this->offering('personal-use', 'Personal Document License', 'Downloadable PDF for personal reference.', 499, 5, ['pdf']),
                    $this->offering('commercial-use', 'Commercial Document License', 'PDF licensed for internal business and commercial reference use.', 1499, 10, ['pdf']),
                    $this->offering('extended-commercial-use', 'Extended Document License', 'PDF with broader organizational usage rights.', 2999, null, ['pdf']),
                ],
            ],
        ];
    }

    private function file(
        string $key,
        string $fixture,
        string $originalFilename,
        string $extension,
        string $mimeType,
        AssetFileRole $role,
        AssetMediaType $mediaType,
        ?int $width = null,
        ?int $height = null,
        bool $isDownloadable = true,
        ?float $durationSeconds = null,
        ?int $pageCount = null,
    ): array {
        return compact(
            'key', 'fixture', 'originalFilename', 'extension', 'mimeType', 'role',
            'mediaType', 'width', 'height', 'isDownloadable', 'durationSeconds', 'pageCount',
        ) + [
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'media_type' => $mediaType,
            'is_downloadable' => $isDownloadable,
            'duration_seconds' => $durationSeconds,
            'page_count' => $pageCount,
        ];
    }

    private function offering(
        string $licenseType,
        string $name,
        string $description,
        int $priceCents,
        ?int $downloadLimit,
        array $files,
        bool $includeAllActiveFiles = false,
        int $imageUnits = 1,
        int $videoUnits = 0,
        int $priceAdjustmentCents = 0,
        ?int $priceOverrideCents = null,
    ): array {
        return [
            'license_type' => $licenseType,
            'name' => $name,
            'description' => $description,
            'image_units' => $imageUnits,
            'video_units' => $videoUnits,
            'price_cents' => $priceCents,
            'price_adjustment_cents' => $priceAdjustmentCents,
            'price_override_cents' => $priceOverrideCents,
            'download_limit' => $downloadLimit,
            'files' => $files,
            'include_all_active_files' => $includeAllActiveFiles,
        ];
    }
}

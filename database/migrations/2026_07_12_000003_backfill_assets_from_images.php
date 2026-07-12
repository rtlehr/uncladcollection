<?php

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileRole;
use App\Enums\AssetFileScanStatus;
use App\Enums\AssetMediaType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('images')->orderBy('id')->chunkById(100, function ($images): void {
            foreach ($images as $image) {
                if (DB::table('assets')->where('legacy_image_id', $image->id)->exists()) {
                    continue;
                }

                $now = now();
                $assetId = DB::table('assets')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'legacy_image_id' => $image->id,
                    'collection_id' => $image->collection_id,
                    'title' => $image->title,
                    'slug' => $image->slug,
                    'description' => $image->description,
                    'asset_type' => AssetType::Image->value,
                    'status' => $image->is_active ? AssetStatus::Published->value : AssetStatus::Draft->value,
                    'photographer' => $image->photographer,
                    'sort_order' => $image->sort_order,
                    'is_active' => $image->is_active,
                    'is_featured' => false,
                    'is_ai_generated' => $image->is_ai_generated,
                    'downloads_count' => $image->downloads_count,
                    'favorites_count' => $image->favorites_count,
                    'purchases_count' => $image->purchases_count,
                    'views_count' => $image->views_count,
                    'published_at' => $image->is_active ? ($image->created_at ?? $now) : null,
                    'metadata' => json_encode(['migration_source' => 'images']),
                    'created_at' => $image->created_at ?? $now,
                    'updated_at' => $image->updated_at ?? $now,
                ]);

                $previewFileId = null;
                $paths = [
                    [AssetFileRole::Primary, 'original_path', true],
                    [AssetFileRole::HighResolution, 'high_res_path', true],
                    [AssetFileRole::Preview, 'thumbnail_path', false],
                    [AssetFileRole::Icon, 'icon_path', false],
                ];

                foreach ($paths as [$role, $column, $downloadable]) {
                    $path = $image->{$column} ?? null;

                    if (! is_string($path) || $path === '') {
                        continue;
                    }

                    $directory = trim(str_replace('\\', '/', dirname($path)), './');
                    $filename = basename(str_replace('\\', '/', $path));
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    $fileId = DB::table('asset_files')->insertGetId([
                        'asset_id' => $assetId,
                        'uuid' => (string) Str::uuid(),
                        'role' => $role->value,
                        'media_type' => AssetMediaType::Image->value,
                        'disk' => 'public',
                        'directory' => $directory === '.' ? '' : $directory,
                        'stored_filename' => $filename,
                        'original_filename' => $filename,
                        'extension' => $extension ?: 'jpg',
                        'mime_type' => match ($extension) {
                            'png' => 'image/png',
                            'webp' => 'image/webp',
                            default => 'image/jpeg',
                        },
                        'sort_order' => match ($role) {
                            AssetFileRole::Primary => 10,
                            AssetFileRole::HighResolution => 20,
                            AssetFileRole::Preview => 30,
                            AssetFileRole::Icon => 40,
                            default => 100,
                        },
                        'processing_status' => AssetFileProcessingStatus::Ready->value,
                        'virus_scan_status' => AssetFileScanStatus::NotRequired->value,
                        'is_downloadable' => $downloadable,
                        'is_active' => true,
                        'is_legacy' => true,
                        'metadata' => json_encode(['legacy_image_column' => $column]),
                        'created_at' => $image->created_at ?? $now,
                        'updated_at' => $image->updated_at ?? $now,
                    ]);

                    if ($role === AssetFileRole::Preview) {
                        $previewFileId = $fileId;
                    }
                }

                if ($previewFileId !== null) {
                    DB::table('assets')->where('id', $assetId)->update([
                        'primary_preview_file_id' => $previewFileId,
                    ]);
                }
            }
        }, 'id');
    }

    public function down(): void
    {
        $legacyAssetIds = DB::table('assets')->whereNotNull('legacy_image_id')->pluck('id');

        DB::table('asset_files')->whereIn('asset_id', $legacyAssetIds)->delete();
        DB::table('assets')->whereIn('id', $legacyAssetIds)->delete();
    }
};

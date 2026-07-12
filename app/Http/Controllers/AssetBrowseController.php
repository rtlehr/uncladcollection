<?php

namespace App\Http\Controllers;

use App\Enums\AssetFileRole;
use App\Enums\AssetMediaType;
use App\Models\Asset;
use App\Models\AssetFile;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetBrowseController extends Controller
{
    public function show(Asset $asset): Response
    {
        abort_unless(
            $asset->is_active
            && $asset->status->value === 'published'
            && ($asset->published_at === null || $asset->published_at->isPast()),
            404,
        );

        $asset->load([
            'collection:id,name,slug,description',
            'activeFiles',
            'primaryPreviewFile',
            'posterFile',
            'legacyImage:id,slug,title',
            'offerings' => fn ($query) => $query
                ->where('is_active', true)
                ->with(['licenseType:id,name,slug,description,usage_terms', 'files'])
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        $asset->increment('views_count');
        $asset->views_count++;

        $files = $asset->activeFiles
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->values();

        $previewFile = $this->resolvePreviewFile($asset, $files);

        $relatedAssets = Asset::query()
            ->published()
            ->whereKeyNot($asset->id)
            ->where(function ($query) use ($asset): void {
                $query->where('asset_type', $asset->asset_type->value);

                if ($asset->collection_id) {
                    $query->orWhere('collection_id', $asset->collection_id);
                }
            })
            ->with(['primaryPreviewFile', 'posterFile', 'activeFiles'])
            ->orderByDesc('is_featured')
            ->orderByDesc('views_count')
            ->limit(6)
            ->get()
            ->map(fn (Asset $related) => $this->formatRelatedAsset($related));

        return Inertia::render('Assets/Show', [
            'asset' => [
                'id' => $asset->id,
                'uuid' => $asset->uuid,
                'title' => $asset->title,
                'slug' => $asset->slug,
                'description' => $asset->description,
                'asset_type' => $asset->asset_type->value,
                'asset_type_label' => $asset->asset_type->label(),
                'photographer' => $asset->photographer,
                'is_ai_generated' => $asset->is_ai_generated,
                'views_count' => $asset->views_count,
                'downloads_count' => $asset->downloads_count,
                'favorites_count' => $asset->favorites_count,
                'published_at' => $asset->published_at?->toDateString(),
                'collection' => $asset->collection
                    ? [
                        'id' => $asset->collection->id,
                        'name' => $asset->collection->name,
                        'slug' => $asset->collection->slug,
                    ]
                    : null,
                'preview' => $previewFile ? $this->formatPreviewFile($asset, $previewFile) : null,
                'poster' => $asset->posterFile && $asset->posterFile->is_active
                    ? $this->formatPreviewFile($asset, $asset->posterFile)
                    : null,
                'files' => $files->map(fn (AssetFile $file) => $this->formatFile($file))->all(),
                'formats' => $files
                    ->pluck('extension')
                    ->filter()
                    ->map(fn (string $extension) => strtoupper($extension))
                    ->unique()
                    ->values()
                    ->all(),
                'legacy_image_url' => $asset->legacyImage
                    ? route('images.show', $asset->legacyImage->slug)
                    : null,
            ],
            'offerings' => $asset->offerings->map(function ($offering) {
                $included = $offering->includedFiles();

                return [
                    'id' => $offering->id,
                    'name' => $offering->name,
                    'description' => $offering->description,
                    'price_cents' => $offering->price_cents,
                    'currency' => $offering->currency,
                    'download_limit' => $offering->download_limit,
                    'expires_after_days' => $offering->expires_after_days,
                    'include_all_active_files' => $offering->include_all_active_files,
                    'license_type' => [
                        'id' => $offering->licenseType->id,
                        'name' => $offering->licenseType->name,
                        'slug' => $offering->licenseType->slug,
                        'description' => $offering->licenseType->description,
                    ],
                    'files' => $included->map(fn (AssetFile $file) => $this->formatFile($file))->values()->all(),
                    'total_size_bytes' => $included->sum('size_bytes'),
                ];
            })->values(),
            'relatedAssets' => $relatedAssets,
        ]);
    }

    public function preview(Asset $asset, AssetFile $assetFile): BinaryFileResponse|StreamedResponse|HttpResponse
    {
        abort_unless($assetFile->asset_id === $asset->id && $assetFile->is_active, 404);
        abort_unless($asset->is_active && $asset->status->value === 'published', 404);
        abort_unless($this->isPublicPreviewFile($asset, $assetFile), 403);
        abort_unless(Storage::disk($assetFile->disk)->exists($assetFile->path), 404);

        $headers = [
            'Content-Type' => $assetFile->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($assetFile->original_filename).'"',
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($assetFile->disk === 'local' || $assetFile->disk === 'asset-files' || $assetFile->disk === 'public') {
            return response()->file(Storage::disk($assetFile->disk)->path($assetFile->path), $headers);
        }

        return response()->stream(function () use ($assetFile): void {
            $stream = Storage::disk($assetFile->disk)->readStream($assetFile->path);
            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, $headers);
    }

    private function resolvePreviewFile(Asset $asset, Collection $files): ?AssetFile
    {
        $explicit = $asset->primaryPreviewFile;
        if ($explicit && $explicit->is_active) {
            return $explicit;
        }

        return $files->first(fn (AssetFile $file) => in_array($file->role, [
            AssetFileRole::Preview,
            AssetFileRole::Poster,
            AssetFileRole::Thumbnail,
            AssetFileRole::Icon,
        ], true) && in_array($file->media_type, [AssetMediaType::Image, AssetMediaType::Vector], true));
    }

    private function isPublicPreviewFile(Asset $asset, AssetFile $file): bool
    {
        return $file->id === $asset->primary_preview_file_id
            || $file->id === $asset->poster_file_id
            || in_array($file->role, [
                AssetFileRole::Preview,
                AssetFileRole::Poster,
                AssetFileRole::Thumbnail,
                AssetFileRole::Icon,
            ], true);
    }

    private function formatPreviewFile(Asset $asset, AssetFile $file): array
    {
        return [
            ...$this->formatFile($file),
            'url' => $file->publicUrl() ?? route('assets.preview', [$asset, $file]),
        ];
    }

    private function formatFile(AssetFile $file): array
    {
        return [
            'id' => $file->id,
            'role' => $file->role->value,
            'role_label' => str($file->role->value)->replace('_', ' ')->title()->toString(),
            'media_type' => $file->media_type->value,
            'original_filename' => $file->original_filename,
            'extension' => strtoupper($file->extension),
            'mime_type' => $file->mime_type,
            'size_bytes' => $file->size_bytes,
            'width' => $file->width,
            'height' => $file->height,
            'duration_seconds' => $file->duration_seconds !== null ? (float) $file->duration_seconds : null,
            'page_count' => $file->page_count,
            'is_downloadable' => $file->is_downloadable,
        ];
    }

    private function formatRelatedAsset(Asset $asset): array
    {
        $files = $asset->activeFiles;
        $preview = $this->resolvePreviewFile($asset, $files);

        return [
            'id' => $asset->id,
            'title' => $asset->title,
            'slug' => $asset->slug,
            'asset_type' => $asset->asset_type->value,
            'asset_type_label' => $asset->asset_type->label(),
            'preview_url' => $preview ? ($preview->publicUrl() ?? route('assets.preview', [$asset, $preview])) : null,
            'formats' => $files->pluck('extension')->filter()->map(fn ($ext) => strtoupper($ext))->unique()->values()->all(),
        ];
    }
}

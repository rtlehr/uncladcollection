<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetFile;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetMediaPresentationService
{
    /** @var array<string, string> */
    private const PREVIEW_KINDS = [
        'jpg' => 'image',
        'jpeg' => 'image',
        'png' => 'image',
        'webp' => 'image',
        'gif' => 'image',
        'svg' => 'image',
        'mp4' => 'video',
        'webm' => 'video',
        'ogg' => 'video',
        'ogv' => 'video',
        'mov' => 'video',
        'pdf' => 'document',
    ];

    public function gallery(Asset $asset, Collection $files, bool $admin = false): array
    {
        $poster = $asset->posterFile && $asset->posterFile->is_active
            ? $asset->posterFile
            : $files->first(fn (AssetFile $file) => $file->role->value === 'poster');

        $posterUrl = $poster && $this->canPreview($poster)
            ? $this->url($asset, $poster, $admin)
            : null;

        return $files
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->values()
            ->map(fn (AssetFile $file) => $this->format($asset, $file, $posterUrl, $admin))
            ->all();
    }

    public function format(Asset $asset, AssetFile $file, ?string $posterUrl = null, bool $admin = false): array
    {
        $canPreview = $this->canPreview($file);

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
            'can_preview' => $canPreview,
            'preview_kind' => $this->previewKind($file),
            'preview_url' => $canPreview ? $this->url($asset, $file, $admin) : null,
            'poster_url' => $this->previewKind($file) === 'video' ? $posterUrl : null,
            'preview_note' => $canPreview ? null : $this->unavailableReason($file),
        ];
    }

    public function canPreview(AssetFile $file): bool
    {
        return $file->is_active
            && array_key_exists(strtolower($file->extension), self::PREVIEW_KINDS);
    }

    public function previewKind(AssetFile $file): string
    {
        return self::PREVIEW_KINDS[strtolower($file->extension)] ?? 'unavailable';
    }

    public function url(Asset $asset, AssetFile $file, bool $admin = false): string
    {
        if ($file->publicUrl()) {
            return $file->publicUrl();
        }

        return $admin
            ? route('admin.assets.files.preview', [$asset, $file])
            : route('assets.preview', [$asset, $file]);
    }

    public function response(AssetFile $file): BinaryFileResponse|StreamedResponse|HttpResponse
    {
        abort_unless($this->canPreview($file), 403);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        $headers = [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($file->original_filename).'"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ];

        if (in_array(strtolower($file->extension), ['svg', 'pdf'], true)) {
            $headers['Content-Security-Policy'] = "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox";
        }

        if (in_array($file->disk, ['local', 'asset-files', 'public'], true)) {
            return response()->file(Storage::disk($file->disk)->path($file->path), $headers);
        }

        return response()->stream(function () use ($file): void {
            $stream = Storage::disk($file->disk)->readStream($file->path);

            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, $headers);
    }

    private function unavailableReason(AssetFile $file): string
    {
        return match (strtolower($file->extension)) {
            'zip' => 'Package contents are listed, but archives are not rendered in the browser.',
            'tif', 'tiff' => 'A browser-friendly TIFF preview has not been generated yet.',
            'eps', 'ai' => 'A raster preview is required for this vector source file.',
            default => 'This format does not have a safe browser preview.',
        };
    }
}

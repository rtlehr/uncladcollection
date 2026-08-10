<?php

namespace App\Http\Controllers\Account;

use App\Enums\AssetFileRole;
use App\Http\Controllers\Controller;
use App\Models\AssetFile;
use App\Models\DesignProject;
use App\Models\License;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DesignLibraryController extends Controller
{
    public function index(Request $request, DesignProject $design): JsonResponse
    {
        $this->authorizeProject($request, $design);

        $search = trim((string) $request->query('search', ''));

        $licenses = License::query()
            ->where('user_id', $request->user()->id)
            ->where('status', License::STATUS_ACTIVE)
            ->whereNotNull('asset_id')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('asset', fn ($assetQuery) => $assetQuery->where('title', 'like', '%'.$search.'%'));
            })
            ->with(['asset.primaryPreviewFile', 'asset.activeFiles', 'licenseType'])
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (License $license): array => [
                'license' => $license,
                'files' => $this->licensedImageFiles($license),
            ])
            ->filter(fn (array $entry): bool => $entry['license']->asset !== null && $entry['files']->isNotEmpty())
            ->unique(fn (array $entry): int => (int) $entry['license']->asset_id)
            ->take(40)
            ->values()
            ->map(function (array $entry) use ($design): array {
                /** @var License $license */
                $license = $entry['license'];
                /** @var Collection<int, AssetFile> $files */
                $files = $entry['files'];
                $asset = $license->asset;
                $preview = $asset?->primaryPreviewFile;
                $fallback = $files->first();

                return [
                    'license_id' => $license->id,
                    'asset_id' => $license->asset_id,
                    'title' => $asset?->title ?? 'Untitled image',
                    'license_name' => $license->license_name ?: $license->licenseType?->name,
                    'licensed_at' => ($license->starts_at ?? $license->created_at)?->format('M j, Y'),
                    'image_count' => $files->count(),
                    'thumbnail_url' => $preview
                        ? route('assets.preview', [$asset, $preview])
                        : route('account.designs.library.files.image', [$design, $license, $fallback]),
                    'files_url' => route('account.designs.library.files.index', [$design, $license]),
                ];
            });

        return response()->json(['items' => $licenses]);
    }

    public function files(Request $request, DesignProject $design, License $license): JsonResponse
    {
        $this->authorizeProject($request, $design);
        $this->authorizeLicense($request, $license);

        $license->loadMissing(['asset', 'licenseType']);
        $files = $this->licensedImageFiles($license);

        abort_unless($license->asset && $files->isNotEmpty(), 404, 'No licensed image files are currently available for this asset.');

        return response()->json([
            'asset' => [
                'license_id' => $license->id,
                'asset_id' => $license->asset_id,
                'title' => $license->asset->title ?? 'Untitled image',
                'license_name' => $license->license_name ?: $license->licenseType?->name,
                'licensed_at' => ($license->starts_at ?? $license->created_at)?->format('M j, Y'),
                'image_count' => $files->count(),
            ],
            'files' => $files->values()->map(function (AssetFile $file) use ($design, $license): array {
                $name = $file->original_filename ?: $file->stored_filename ?: 'Licensed image';

                return [
                    'id' => $file->id,
                    'uuid' => $file->uuid,
                    'name' => $name,
                    'role' => ucfirst(str_replace('_', ' ', $file->role?->value ?? (string) $file->role)),
                    'format' => strtoupper((string) ($file->extension ?: pathinfo($name, PATHINFO_EXTENSION))),
                    'width' => $file->width,
                    'height' => $file->height,
                    'image_url' => route('account.designs.library.files.image', [$design, $license, $file]),
                    'thumbnail_url' => route('account.designs.library.files.image', [$design, $license, $file]),
                ];
            })->all(),
        ]);
    }

    /**
     * Backwards-compatible endpoint for saved designs created before the
     * library picker supported multiple files per asset.
     */
    public function image(Request $request, DesignProject $design, License $license): StreamedResponse
    {
        $this->authorizeProject($request, $design);
        $this->authorizeLicense($request, $license);

        $file = $this->licensedImageFiles($license)->first();
        abort_unless($file, 404, 'No licensed image file is currently available for this asset.');

        return $this->streamFile($file);
    }

    public function fileImage(Request $request, DesignProject $design, License $license, AssetFile $assetFile): StreamedResponse
    {
        $this->authorizeProject($request, $design);
        $this->authorizeLicense($request, $license);

        $file = $this->licensedImageFiles($license)
            ->first(fn (AssetFile $candidate): bool => $candidate->is($assetFile));

        abort_unless($file, 404, 'This image file is not included in your purchased license or is no longer available.');

        return $this->streamFile($file);
    }

    private function authorizeProject(Request $request, DesignProject $design): void
    {
        abort_unless((int) $design->user_id === (int) $request->user()->id, 403);
    }

    private function authorizeLicense(Request $request, License $license): void
    {
        abort_unless((int) $license->user_id === (int) $request->user()->id && $license->isActive(), 403);
    }

    /** @return Collection<int, AssetFile> */
    private function licensedImageFiles(License $license): Collection
    {
        $license->loadMissing('asset.activeFiles');
        if (! $license->asset || ! $license->isActive()) {
            return collect();
        }

        $snapshot = collect($license->included_asset_files_snapshot ?? []);
        $ids = $snapshot->pluck('asset_file_id')->filter()->map(fn ($id) => (int) $id);
        $uuids = $snapshot->pluck('uuid')->filter()->map(fn ($uuid) => (string) $uuid);

        /** @var Collection<int, AssetFile> $files */
        $files = $license->asset->activeFiles
            ->filter(function (AssetFile $file) use ($ids, $uuids): bool {
                $isIncluded = ($ids->isEmpty() && $uuids->isEmpty())
                    || $ids->contains((int) $file->id)
                    || $uuids->contains((string) $file->uuid);

                return $isIncluded
                    && $file->is_downloadable
                    && str_starts_with((string) $file->mime_type, 'image/')
                    && $file->exists();
            });

        $priority = [
            AssetFileRole::Source->value => 0,
            AssetFileRole::Print->value => 1,
            AssetFileRole::HighResolution->value => 2,
            AssetFileRole::Primary->value => 3,
            AssetFileRole::Preview->value => 4,
        ];

        return $files
            ->sortBy(fn (AssetFile $file) => [
                $priority[$file->role?->value ?? (string) $file->role] ?? 9,
                (int) $file->sort_order,
                -((int) $file->width * (int) $file->height),
                (int) $file->id,
            ])
            ->values();
    }

    private function streamFile(AssetFile $file): StreamedResponse
    {
        return response()->stream(function () use ($file): void {
            $stream = Storage::disk($file->disk)->readStream($file->path);
            abort_unless($stream !== false, 404);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes(basename($file->original_filename ?: $file->stored_filename)).'"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

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
            ->filter(fn (License $license): bool => $license->asset !== null && $this->licensedImageFile($license) !== null)
            ->unique('asset_id')
            ->take(40)
            ->values()
            ->map(function (License $license) use ($design): array {
                $asset = $license->asset;
                $preview = $asset?->primaryPreviewFile;

                return [
                    'license_id' => $license->id,
                    'asset_id' => $license->asset_id,
                    'title' => $asset?->title ?? 'Untitled image',
                    'license_name' => $license->license_name ?: $license->licenseType?->name,
                    'licensed_at' => ($license->starts_at ?? $license->created_at)?->format('M j, Y'),
                    'thumbnail_url' => $preview
                        ? route('assets.preview', [$asset, $preview])
                        : route('account.designs.library.image', [$design, $license]),
                    'image_url' => route('account.designs.library.image', [$design, $license]),
                ];
            });

        return response()->json(['items' => $licenses]);
    }

    public function image(Request $request, DesignProject $design, License $license): StreamedResponse
    {
        $this->authorizeProject($request, $design);
        abort_unless((int) $license->user_id === (int) $request->user()->id && $license->isActive(), 403);

        $file = $this->licensedImageFile($license);
        abort_unless($file, 404, 'No licensed image file is currently available for this asset.');

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

    private function authorizeProject(Request $request, DesignProject $design): void
    {
        abort_unless((int) $design->user_id === (int) $request->user()->id, 403);
    }

    private function licensedImageFile(License $license): ?AssetFile
    {
        $license->loadMissing('asset.activeFiles');
        if (! $license->asset || ! $license->isActive()) {
            return null;
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
                -((int) $file->width * (int) $file->height),
            ])
            ->first();
    }
}

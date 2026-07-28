<?php

namespace App\Services\Downloads;

use App\Models\AssetFile;
use App\Models\License;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DownloadEntitlementService
{
    public function authorizeLicense(User $user, License $license): License
    {
        abort_unless((int) $license->user_id === (int) $user->id, 403);
        abort_unless($license->asset_id !== null, 404, 'This license does not contain Asset files.');
        abort_unless($license->canDownload(), 403, 'This license cannot currently be downloaded.');

        return $license;
    }

    /** @return Collection<int, AssetFile> */
    public function availableFiles(User $user, License $license): Collection
    {
        $this->authorizeLicense($user, $license);

        $snapshot = collect($license->included_asset_files_snapshot ?? []);
        $ids = $snapshot->pluck('asset_file_id')->filter()->map(fn ($id) => (int) $id)->values();
        $uuids = $snapshot->pluck('uuid')->filter()->map(fn ($uuid) => (string) $uuid)->values();

        return AssetFile::query()
            ->where('asset_id', $license->asset_id)
            ->where('is_active', true)
            ->where('is_downloadable', true)
            ->where(function ($query) use ($ids, $uuids): void {
                if ($ids->isNotEmpty()) {
                    $query->whereIn('id', $ids);
                }
                if ($uuids->isNotEmpty()) {
                    $ids->isNotEmpty()
                        ? $query->orWhereIn('uuid', $uuids)
                        : $query->whereIn('uuid', $uuids);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (AssetFile $file): bool => $file->exists())
            ->values();
    }

    public function authorizeFile(User $user, License $license, AssetFile $assetFile): AssetFile
    {
        $files = $this->availableFiles($user, $license);
        $allowed = $files->first(fn (AssetFile $file): bool => $file->is($assetFile));

        abort_unless($allowed, 404, 'This file is not included in your purchased license or is no longer available.');

        return $allowed;
    }
}

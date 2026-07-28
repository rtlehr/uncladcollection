<?php

namespace App\Services\Downloads;

use App\Models\AssetFile;
use App\Models\Download;
use App\Models\License;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Notifications\CustomerNotificationService;

class DownloadRecorder
{
    public function __construct(private readonly CustomerNotificationService $notifications) {}
    public function recordFile(User $user, License $license, AssetFile $file, Request $request, ?string $batchUuid = null): Download
    {
        return DB::transaction(function () use ($user, $license, $file, $request, $batchUuid): Download {
            $locked = License::query()
                ->whereKey($license->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($locked->canDownload(), 403, 'This license cannot currently be downloaded.');

            $download = Download::create([
                'user_id' => $user->id,
                'image_id' => null,
                'asset_id' => $locked->asset_id,
                'asset_file_id' => $file->id,
                'license_id' => $locked->id,
                'order_item_id' => $locked->order_item_id,
                'batch_uuid' => $batchUuid,
                'download_type' => $batchUuid ? 'asset_package_file' : 'asset_file',
                'source' => 'customer',
                'original_filename' => $file->original_filename,
                'size_bytes' => $file->size_bytes,
                'status' => 'completed',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'downloaded_at' => now(),
            ]);

            if ($batchUuid === null) {
                $locked->increment('downloads_used');
                $locked->refresh();
                $this->warnIfLow($locked, $user);
            }

            return $download;
        }, attempts: 3);
    }

    /** @param iterable<int, AssetFile> $files */
    public function recordPackage(User $user, License $license, iterable $files, Request $request, string $batchUuid): void
    {
        DB::transaction(function () use ($user, $license, $files, $request, $batchUuid): void {
            $locked = License::query()
                ->whereKey($license->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($locked->canDownload(), 403, 'This license cannot currently be downloaded.');

            foreach ($files as $file) {
                Download::create([
                    'user_id' => $user->id,
                    'image_id' => null,
                    'asset_id' => $locked->asset_id,
                    'asset_file_id' => $file->id,
                    'license_id' => $locked->id,
                    'order_item_id' => $locked->order_item_id,
                    'batch_uuid' => $batchUuid,
                    'download_type' => 'asset_package_file',
                    'source' => 'customer',
                    'original_filename' => $file->original_filename,
                    'size_bytes' => $file->size_bytes,
                    'status' => 'completed',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'downloaded_at' => now(),
                ]);
            }

            $locked->increment('downloads_used');
            $locked->refresh();
            $this->warnIfLow($locked, $user);
        }, attempts: 3);
    }

    private function warnIfLow(License $license, User $user): void
    {
        if ($license->download_limit === null) return;
        $remaining = max(0, $license->download_limit - $license->downloads_used);
        if ($remaining > 1) return;
        DB::afterCommit(function () use ($license, $user, $remaining): void {
            $message = $remaining === 0 ? 'The download allowance for this license has been used.' : 'One download remains for this license.';
            $this->notifications->send($user, 'downloads', $remaining === 0 ? 'Download limit reached' : 'Download limit nearly reached', $message, route('account.licenses.show', $license), 'View license', ['license_id' => $license->id, 'remaining' => $remaining]);
        });
    }
}

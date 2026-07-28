<?php

namespace App\Http\Controllers;

use App\Models\AssetFile;
use App\Models\License;
use App\Services\Downloads\AssetPackageService;
use App\Services\Downloads\DownloadEntitlementService;
use App\Services\Downloads\DownloadRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountDownloadController extends Controller
{
    public function __construct(
        private readonly DownloadEntitlementService $entitlements,
        private readonly DownloadRecorder $recorder,
        private readonly AssetPackageService $packages,
    ) {}

    public function file(Request $request, License $license, AssetFile $assetFile): StreamedResponse
    {
        $file = $this->entitlements->authorizeFile($request->user(), $license, $assetFile);
        $this->recorder->recordFile($request->user(), $license, $file, $request);

        return response()->streamDownload(function () use ($file): void {
            $stream = Storage::disk($file->disk)->readStream($file->path);
            abort_unless($stream !== false, 404);
            fpassthru($stream);
            fclose($stream);
        }, basename($file->original_filename ?: $file->stored_filename), [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function package(Request $request, License $license): BinaryFileResponse
    {
        $license->loadMissing('asset');
        $files = $this->entitlements->availableFiles($request->user(), $license);
        abort_if($files->isEmpty(), 404, 'No licensed files are currently available.');

        $batchUuid = (string) Str::uuid();
        $package = $this->packages->build($license, $files);
        $this->recorder->recordPackage($request->user(), $license, $files, $request, $batchUuid);

        return response()->download($package['path'], $package['filename'], [
            'Content-Type' => 'application/zip',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }
}

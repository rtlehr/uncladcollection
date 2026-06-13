<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Image;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {
    }

    public function download(Request $request, Image $image): StreamedResponse
    {
        $license = $this->purchaseService->getActiveLicenseForImage(
            $request->user(),
            $image
        );

        abort_unless($license, 403, 'You do not have an active license for this image.');

        abort_unless($license->canDownload(), 403, 'This license cannot be downloaded.');

        $path = match ($license->licenseType?->max_resolution) {
            'original' => $image->original_path,
            'high_res' => $image->high_res_path,
            'thumbnail' => $image->thumbnail_path,
            'icon' => $image->icon_path,
            default => $image->high_res_path,
        };

        abort_unless($path && Storage::disk('public')->exists($path), 404, 'Download file not found.');

        Download::create([
            'user_id' => $request->user()->id,
            'image_id' => $image->id,
            'license_id' => $license->id,
            'order_item_id' => $license->order_item_id,
            'download_type' => $license->licenseType?->max_resolution ?? 'high_res',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'downloaded_at' => now(),
        ]);

        $license->increment('downloads_used');
        $image->increment('downloads_count');

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';

        $filename = str($image->slug)
            ->append('-')
            ->append($license->licenseType?->max_resolution ?? 'high-res')
            ->append('.')
            ->append($extension)
            ->toString();

        return Storage::disk('public')->download($path, $filename);
    }
}
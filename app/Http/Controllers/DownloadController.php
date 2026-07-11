<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Image;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {
    }

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $sort = $request->string('sort')->toString() ?: 'downloaded_at';
        $direction = $request->string('direction')->toString() ?: 'desc';

        $allowedSorts = [
            'downloaded_at',
            'download_type',
            'ip_address',
        ];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'downloaded_at';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $downloads = Download::query()
            ->with([
                'user',
                'image',
                'license.licenseType',
                'orderItem.order',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('download_type', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('image', function ($query) use ($search) {
                            $query->where('title', 'like', "%{$search}%");
                        })
                        ->orWhereHas('license', function ($query) use ($search) {
                            $query->where('license_key', 'like', "%{$search}%");
                        })
                        ->orWhereHas('license.licenseType', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('orderItem.order', function ($query) use ($search) {
                            $query->where('order_number', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->withQueryString()
            ->through(function (Download $download) {
                $order = $download->orderItem?->order;

                return [
                    'id' => $download->id,
                    'download_type' => $download->download_type,
                    'ip_address' => $download->ip_address,
                    'downloaded_at' => $download->downloaded_at?->format(
                        'Y-m-d H:i'
                    ),

                    'user' => $download->user
                        ? [
                            'id' => $download->user->id,
                            'name' => $download->user->name,
                            'email' => $download->user->email,
                        ]
                        : null,

                    'image' => $download->image
                        ? [
                            'id' => $download->image->id,
                            'title' => $download->image->title,
                            'slug' => $download->image->slug,
                        ]
                        : null,

                    'license' => $download->license
                        ? [
                            'id' => $download->license->id,
                            'license_key' => $download->license->license_key,
                            'license_name' => $download->license->license_name
                                ?? $download->license->licenseType?->name
                                ?? 'License',
                        ]
                        : null,

                    'order' => $order
                        ? [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                        ]
                        : null,
                ];
            });

        return Inertia::render('Admin/Downloads/Index', [
            'downloads' => $downloads,

            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function download(
        Request $request,
        Image $image
    ): StreamedResponse {
        $license = $this->purchaseService->getActiveLicenseForImage(
            $request->user(),
            $image
        );

        abort_unless(
            $license,
            403,
            'You do not have an active license for this image.'
        );

        abort_unless(
            $license->canDownload(),
            403,
            'This license cannot be downloaded.'
        );

        $path = match ($license->licenseType?->max_resolution) {
            'original' => $image->original_path,
            'high_res' => $image->high_res_path,
            'thumbnail' => $image->thumbnail_path,
            'icon' => $image->icon_path,
            default => $image->high_res_path,
        };

        abort_unless(
            $path && Storage::disk('public')->exists($path),
            404,
            'Download file not found.'
        );

        Download::create([
            'user_id' => $request->user()->id,
            'image_id' => $image->id,
            'license_id' => $license->id,
            'order_item_id' => $license->order_item_id,
            'download_type' => $license->licenseType?->max_resolution
                ?? 'high_res',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'downloaded_at' => now(),
        ]);

        $license->increment('downloads_used');
        $image->increment('downloads_count');

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';

        $filename = str($image->slug)
            ->append('-')
            ->append(
                $license->licenseType?->max_resolution
                    ?? 'high-res'
            )
            ->append('.')
            ->append($extension)
            ->toString();

        return Storage::disk('public')->download(
            $path,
            $filename
        );
    }
}
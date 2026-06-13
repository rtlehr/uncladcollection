<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;

class AdminDownloadController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $downloads = Download::query()
            ->with(['user', 'image', 'license', 'orderItem.order'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('download_type', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('image', function ($query) use ($search) {
                            $query->where('title', 'like', "%{$search}%");
                        })
                        ->orWhereHas('license', function ($query) use ($search) {
                            $query->where('license_key', 'like', "%{$search}%")
                                ->orWhere('license_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('orderItem.order', function ($query) use ($search) {
                            $query->where('order_number', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('downloaded_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Download $download) => [
                'id' => $download->id,
                'download_type' => $download->download_type,
                'ip_address' => $download->ip_address,
                'downloaded_at' => $download->downloaded_at?->format('Y-m-d H:i'),

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
                        'photographer' => $download->image->photographer,
                        'icon_url' => $download->image->icon_path
                            ? Storage::url($download->image->icon_path)
                            : null,
                    ]
                    : null,

                'license' => $download->license
                    ? [
                        'id' => $download->license->id,
                        'license_key' => $download->license->license_key,
                        'license_name' => $download->license->license_name,
                    ]
                    : null,

                'order' => $download->orderItem?->order
                    ? [
                        'id' => $download->orderItem->order->id,
                        'order_number' => $download->orderItem->order->order_number,
                    ]
                    : null,
            ]);

        return Inertia::render('Admin/Downloads/Index', [
            'downloads' => $downloads,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(Download $download): Response
    {
        $download->load([
            'user',
            'image',
            'license.order',
            'license.licenseType',
            'orderItem.order',
        ]);

        return Inertia::render('Admin/Downloads/Show', [
            'downloadRecord' => [
                'id' => $download->id,
                'download_type' => $download->download_type,
                'ip_address' => $download->ip_address,
                'user_agent' => $download->user_agent,
                'downloaded_at' => $download->downloaded_at?->format('Y-m-d H:i'),
                'created_at' => $download->created_at?->format('Y-m-d H:i'),

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
                        'photographer' => $download->image->photographer,
                        'icon_url' => $download->image->icon_path
                            ? Storage::url($download->image->icon_path)
                            : null,
                    ]
                    : null,

                'license' => $download->license
                    ? [
                        'id' => $download->license->id,
                        'license_key' => $download->license->license_key,
                        'license_name' => $download->license->license_name,
                        'status' => $download->license->status,
                        'downloads_used' => $download->license->downloads_used,
                        'download_limit' => $download->license->download_limit,
                        'order_id' => $download->license->order_id,
                    ]
                    : null,

                'order' => $download->license?->order
                    ? [
                        'id' => $download->license->order->id,
                        'order_number' => $download->license->order->order_number,
                        'status' => $download->license->order->status,
                        'total_formatted' => $download->license->order->total_formatted,
                        'paid_at' => $download->license->order->paid_at?->format('Y-m-d H:i'),
                    ]
                    : null,
            ],
        ]);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminLicenseController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $licenses = License::query()
            ->with(['user', 'image', 'licenseType', 'order'])
            ->withCount('downloads')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('license_key', 'like', "%{$search}%")
                        ->orWhere('license_name', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('image', function ($query) use ($search) {
                            $query->where('title', 'like', "%{$search}%");
                        })
                        ->orWhereHas('order', function ($query) use ($search) {
                            $query->where('order_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (License $license) => [
                'id' => $license->id,
                'license_key' => $license->license_key,
                'status' => $license->status,
                'license_name' => $license->license_name,
                'downloads_used' => $license->downloads_used,
                'download_limit' => $license->download_limit,
                'downloads_count' => $license->downloads_count,
                'starts_at' => $license->starts_at?->format('Y-m-d'),
                'expires_at' => $license->expires_at?->format('Y-m-d'),
                'created_at' => $license->created_at?->format('Y-m-d H:i'),

                'user' => $license->user
                    ? [
                        'id' => $license->user->id,
                        'name' => $license->user->name,
                        'email' => $license->user->email,
                    ]
                    : null,

                'image' => $license->image
                    ? [
                        'id' => $license->image->id,
                        'title' => $license->image->title,
                        'slug' => $license->image->slug,
                    ]
                    : null,

                'order' => $license->order
                    ? [
                        'id' => $license->order->id,
                        'order_number' => $license->order->order_number,
                    ]
                    : null,
            ]);

        return Inertia::render('Admin/Licenses/Index', [
            'licenses' => $licenses,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statuses' => [
                License::STATUS_ACTIVE,
                License::STATUS_EXPIRED,
                License::STATUS_REVOKED,
                License::STATUS_REFUNDED,
            ],
        ]);
    }

    public function show(License $license): Response
    {
        $license->load([
            'user',
            'image.collection',
            'image.categories',
            'image.tags',
            'licenseType',
            'order',
            'orderItem',
            'downloads.user',
        ]);

        return Inertia::render('Admin/Licenses/Show', [
            'licenseRecord' => [
                'id' => $license->id,
                'license_key' => $license->license_key,
                'status' => $license->status,
                'license_name' => $license->license_name,
                'license_terms' => $license->license_terms,
                'downloads_used' => $license->downloads_used,
                'download_limit' => $license->download_limit,
                'starts_at' => $license->starts_at?->format('Y-m-d'),
                'expires_at' => $license->expires_at?->format('Y-m-d'),
                'created_at' => $license->created_at?->format('Y-m-d H:i'),

                'user' => $license->user
                    ? [
                        'id' => $license->user->id,
                        'name' => $license->user->name,
                        'email' => $license->user->email,
                    ]
                    : null,

                'image' => $license->image
                    ? [
                        'id' => $license->image->id,
                        'title' => $license->image->title,
                        'slug' => $license->image->slug,
                        'photographer' => $license->image->photographer,
                        'is_ai_generated' => $license->image->is_ai_generated,
                    ]
                    : null,

                'order' => $license->order
                    ? [
                        'id' => $license->order->id,
                        'order_number' => $license->order->order_number,
                        'status' => $license->order->status,
                        'total_formatted' => $license->order->total_formatted,
                        'paid_at' => $license->order->paid_at?->format('Y-m-d H:i'),
                    ]
                    : null,

                'order_item' => $license->orderItem
                    ? [
                        'id' => $license->orderItem->id,
                        'status' => $license->orderItem->status,
                        'unit_price_formatted' => $license->orderItem->unit_price_formatted,
                        'total_price_formatted' => $license->orderItem->total_price_formatted,
                    ]
                    : null,

                'downloads' => $license->downloads
                    ->sortByDesc('downloaded_at')
                    ->map(fn ($download) => [
                        'id' => $download->id,
                        'download_type' => $download->download_type,
                        'ip_address' => $download->ip_address,
                        'user_agent' => $download->user_agent,
                        'downloaded_at' => $download->downloaded_at?->format('Y-m-d H:i'),
                    ])
                    ->values(),
            ],
        ]);
    }
}
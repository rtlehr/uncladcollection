<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Image;
use App\Models\License;
use App\Models\Order;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $totalRevenueCents = Order::query()
            ->where('status', Order::STATUS_PAID)
            ->sum('total_cents');

        $recentOrders = Order::query()
            ->with('user')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'total_formatted' => $order->total_formatted,
                'created_at' => $order->created_at?->format('Y-m-d H:i'),
                'user' => $order->user
                    ? [
                        'name' => $order->user->name,
                        'email' => $order->user->email,
                    ]
                    : null,
            ]);

        $recentDownloads = Download::query()
            ->with(['user', 'image', 'license'])
            ->latest('downloaded_at')
            ->limit(5)
            ->get()
            ->map(fn (Download $download) => [
                'id' => $download->id,
                'download_type' => $download->download_type,
                'downloaded_at' => $download->downloaded_at?->format('Y-m-d H:i'),
                'user' => $download->user
                    ? [
                        'name' => $download->user->name,
                        'email' => $download->user->email,
                    ]
                    : null,
                'image' => $download->image
                    ? [
                        'title' => $download->image->title,
                        'slug' => $download->image->slug,
                    ]
                    : null,
                'license' => $download->license
                    ? [
                        'id' => $download->license->id,
                        'license_name' => $download->license->license_name,
                    ]
                    : null,
            ]);

        $topPurchasedImages = Image::query()
            ->where('purchases_count', '>', 0)
            ->orderByDesc('purchases_count')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'purchases_count', 'downloads_count'])
            ->map(fn (Image $image) => [
                'id' => $image->id,
                'title' => $image->title,
                'slug' => $image->slug,
                'purchases_count' => $image->purchases_count,
                'downloads_count' => $image->downloads_count,
            ]);

        $topDownloadedImages = Image::query()
            ->where('downloads_count', '>', 0)
            ->orderByDesc('downloads_count')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'purchases_count', 'downloads_count'])
            ->map(fn (Image $image) => [
                'id' => $image->id,
                'title' => $image->title,
                'slug' => $image->slug,
                'purchases_count' => $image->purchases_count,
                'downloads_count' => $image->downloads_count,
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_revenue_formatted' => '$' . number_format($totalRevenueCents / 100, 2),
                'total_orders' => Order::count(),
                'paid_orders' => Order::where('status', Order::STATUS_PAID)->count(),
                'active_licenses' => License::where('status', License::STATUS_ACTIVE)->count(),
                'total_downloads' => Download::count(),
                'total_images' => Image::count(),
                'active_images' => Image::where('is_active', true)->count(),
                'total_users' => User::count(),
            ],
            'recentOrders' => $recentOrders,
            'recentDownloads' => $recentDownloads,
            'topPurchasedImages' => $topPurchasedImages,
            'topDownloadedImages' => $topDownloadedImages,
        ]);
    }
}
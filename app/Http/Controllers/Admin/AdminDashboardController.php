<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Image;
use App\Models\License;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $dashboardSummary = Cache::remember(
            key: 'admin-dashboard:summary',
            ttl: now()->addSeconds(60),
            callback: function (): array {
                $totalRevenueCents = Order::query()
                    ->where('status', Order::STATUS_PAID)
                    ->sum('total_cents');

                return [
                    'stats' => [
                        'total_revenue_formatted' => '$' . number_format($totalRevenueCents / 100, 2),
                        'total_orders' => Order::query()->count(),
                        'paid_orders' => Order::query()
                            ->where('status', Order::STATUS_PAID)
                            ->count(),
                        'active_licenses' => License::query()
                            ->where('status', License::STATUS_ACTIVE)
                            ->count(),
                        'total_downloads' => Download::query()->count(),
                        'total_images' => Image::query()->count(),
                        'active_images' => Image::query()
                            ->where('is_active', true)
                            ->count(),
                        'total_users' => User::query()->count(),
                    ],

                    'topPurchasedImages' => Image::query()
                        ->where('purchases_count', '>', 0)
                        ->orderByDesc('purchases_count')
                        ->limit(5)
                        ->get([
                            'id',
                            'title',
                            'slug',
                            'purchases_count',
                            'downloads_count',
                        ])
                        ->map(fn (Image $image) => [
                            'id' => $image->id,
                            'title' => $image->title,
                            'slug' => $image->slug,
                            'purchases_count' => $image->purchases_count,
                            'downloads_count' => $image->downloads_count,
                        ])
                        ->values(),

                    'topDownloadedImages' => Image::query()
                        ->where('downloads_count', '>', 0)
                        ->orderByDesc('downloads_count')
                        ->limit(5)
                        ->get([
                            'id',
                            'title',
                            'slug',
                            'purchases_count',
                            'downloads_count',
                        ])
                        ->map(fn (Image $image) => [
                            'id' => $image->id,
                            'title' => $image->title,
                            'slug' => $image->slug,
                            'purchases_count' => $image->purchases_count,
                            'downloads_count' => $image->downloads_count,
                        ])
                        ->values(),
                ];
            },
        );

        $recentOrders = Order::query()
            ->select([
                'id',
                'user_id',
                'order_number',
                'status',
                'total_cents',
                'currency',
                'created_at',
            ])
            ->with('user:id,name,email')
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
            ])
            ->values();

        $recentDownloads = Download::query()
            ->select([
                'id',
                'user_id',
                'image_id',
                'license_id',
                'download_type',
                'downloaded_at',
            ])
            ->with([
                'user:id,name,email',
                'image:id,title,slug',
                'license:id,license_name',
            ])
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
            ])
            ->values();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $dashboardSummary['stats'],
            'recentOrders' => $recentOrders,
            'recentDownloads' => $recentDownloads,
            'topPurchasedImages' => $dashboardSummary['topPurchasedImages'],
            'topDownloadedImages' => $dashboardSummary['topDownloadedImages'],
        ]);
    }
}

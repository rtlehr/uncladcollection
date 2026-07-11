<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $sort = $request->string('sort')->toString() ?: 'created_at';
        $direction = $request->string('direction')->toString() ?: 'desc';

        $allowedSorts = [
            'order_number',
            'status',
            'total_cents',
            'paid_at',
            'created_at',
        ];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $orders = Order::query()
            ->with('user')
            ->withCount(['items', 'licenses'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('items.image', function ($query) use ($search) {
                            $query->where('title', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'total_formatted' => $order->total_formatted,
                'currency' => $order->currency,
                'payment_provider' => $order->payment_provider,
                'paid_at' => $order->paid_at?->format('Y-m-d H:i'),
                'created_at' => $order->created_at?->format('Y-m-d H:i'),
                'items_count' => $order->items_count,
                'licenses_count' => $order->licenses_count,

                'user' => $order->user
                    ? [
                        'id' => $order->user->id,
                        'name' => $order->user->name,
                        'email' => $order->user->email,
                    ]
                    : null,
            ]);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,

            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
            ],

            'statuses' => [
                Order::STATUS_PENDING,
                Order::STATUS_PAID,
                Order::STATUS_FAILED,
                Order::STATUS_CANCELED,
                Order::STATUS_REFUNDED,
                Order::STATUS_PARTIALLY_REFUNDED,
            ],
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load([
            'user',
            'items.image',
            'items.licenseType',
            'licenses.image',
            'licenses.licenseType',
            'licenses.downloads',
        ]);

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'subtotal_formatted' => $order->subtotal_formatted,
                'total_formatted' => $order->total_formatted,
                'subtotal_cents' => $order->subtotal_cents,
                'discount_cents' => $order->discount_cents,
                'tax_cents' => $order->tax_cents,
                'total_cents' => $order->total_cents,
                'currency' => $order->currency,
                'payment_provider' => $order->payment_provider,
                'payment_reference' => $order->payment_reference,
                'stripe_checkout_session_id' => $order->stripe_checkout_session_id,
                'stripe_payment_intent_id' => $order->stripe_payment_intent_id,
                'paid_at' => $order->paid_at?->format('Y-m-d H:i'),
                'refunded_at' => $order->refunded_at?->format('Y-m-d H:i'),
                'canceled_at' => $order->canceled_at?->format('Y-m-d H:i'),
                'created_at' => $order->created_at?->format('Y-m-d H:i'),

                'user' => $order->user
                    ? [
                        'id' => $order->user->id,
                        'name' => $order->user->name,
                        'email' => $order->user->email,
                    ]
                    : null,

                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'status' => $item->status,
                    'quantity' => $item->quantity,
                    'unit_price_formatted' => $item->unit_price_formatted,
                    'total_price_formatted' => $item->total_price_formatted,
                    'image_title' => $item->image_title,
                    'license_name' => $item->license_name,

                    'image' => $item->image
                        ? [
                            'id' => $item->image->id,
                            'title' => $item->image->title,
                            'slug' => $item->image->slug,
                        ]
                        : null,
                ])->values(),

                'licenses' => $order->licenses->map(fn ($license) => [
                    'id' => $license->id,
                    'license_key' => $license->license_key,
                    'status' => $license->status,
                    'license_name' => $license->license_name,
                    'downloads_used' => $license->downloads_used,
                    'download_limit' => $license->download_limit,
                    'starts_at' => $license->starts_at?->format('Y-m-d'),
                    'expires_at' => $license->expires_at?->format('Y-m-d'),
                    'downloads_count' => $license->downloads->count(),

                    'image' => $license->image
                        ? [
                            'id' => $license->image->id,
                            'title' => $license->image->title,
                            'slug' => $license->image->slug,
                        ]
                        : null,
                ])->values(),
            ],
        ]);
    }
}
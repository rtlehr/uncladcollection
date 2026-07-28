<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->withCount('items')
            ->latest()
            ->paginate(12)
            ->through(fn (Order $order): array => $this->summary($order));

        return Inertia::render('Account/Orders/Index', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order): Response
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);

        $order->load([
            'items.asset.primaryPreviewFile',
            'items.image',
            'items.license',
            'fulfillmentEvents.user:id,name',
            'financialTransactions',
        ]);

        return Inertia::render('Account/Orders/Show', [
            'order' => array_merge($this->summary($order), [
                'subtotal_formatted' => '$'.number_format($order->subtotal_cents / 100, 2),
                'discount_formatted' => '$'.number_format($order->discount_cents / 100, 2),
                'tax_formatted' => '$'.number_format($order->tax_cents / 100, 2),
                'shipping_carrier' => $order->shipping_carrier,
                'tracking_number' => $order->tracking_number,
                'paid_at' => $order->paid_at?->format('M j, Y g:i A'),
                'refunded_at' => $order->refunded_at?->format('M j, Y g:i A'),
                'canceled_at' => $order->canceled_at?->format('M j, Y g:i A'),
                'shipped_at' => $order->shipped_at?->format('M j, Y g:i A'),
                'delivered_at' => $order->delivered_at?->format('M j, Y g:i A'),
                'items' => $order->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'title' => $item->asset_title ?: $item->image_title ?: 'Purchased item',
                    'license_name' => $item->license_name,
                    'offering_name' => $item->offering_name,
                    'quantity' => $item->quantity,
                    'status' => $item->status,
                    'fulfillment_type' => $item->fulfillment_type,
                    'total_formatted' => $item->total_price_formatted,
                    'license_url' => $item->license ? route('account.licenses.show', $item->license) : null,
                ])->values()->all(),
                'timeline' => $this->timeline($order),
            ]),
        ]);
    }

    private function summary(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => str($order->status)->replace('_', ' ')->title()->toString(),
            'fulfillment_status' => $order->fulfillment_status?->value,
            'fulfillment_label' => $order->fulfillment_status?->label(),
            'total_formatted' => $order->total_formatted,
            'currency' => strtoupper($order->currency ?: 'USD'),
            'items_count' => $order->items_count ?? $order->items->count(),
            'created_at' => $order->created_at?->format('M j, Y'),
            'show_url' => route('account.orders.show', $order),
        ];
    }

    private function timeline(Order $order): array
    {
        $events = collect();
        $events->push(['label' => 'Order placed', 'detail' => 'Your order was created.', 'occurred_at' => $order->created_at]);
        if ($order->paid_at) $events->push(['label' => 'Payment received', 'detail' => 'Payment was completed successfully.', 'occurred_at' => $order->paid_at]);
        foreach ($order->fulfillmentEvents as $event) {
            $events->push([
                'label' => str($event->status)->replace('_', ' ')->title()->toString(),
                'detail' => $event->note ?: 'Fulfillment status updated.',
                'occurred_at' => $event->created_at,
            ]);
        }
        if ($order->refunded_at) $events->push(['label' => 'Refund recorded', 'detail' => 'A refund was recorded for this order.', 'occurred_at' => $order->refunded_at]);
        if ($order->canceled_at) $events->push(['label' => 'Order canceled', 'detail' => 'This order was canceled.', 'occurred_at' => $order->canceled_at]);

        return $events->sortBy('occurred_at')->values()->map(fn (array $event): array => [
            'label' => $event['label'],
            'detail' => $event['detail'],
            'occurred_at' => $event['occurred_at']?->format('M j, Y g:i A'),
        ])->all();
    }
}

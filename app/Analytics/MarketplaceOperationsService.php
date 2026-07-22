<?php

namespace App\Analytics;

use App\Enums\OrderFulfillmentStatus;
use App\Models\Order;
use Illuminate\Support\Collection;

class MarketplaceOperationsService
{
    public function report(AnalyticsPeriod $period, array $filters = []): array
    {
        $rows = $this->orderRows($period, $filters);
        $paid = $rows->where('status', Order::STATUS_PAID);
        $completed = $rows->filter(fn (array $row) => in_array($row['fulfillment_status'], ['delivered', 'fulfilled'], true));

        return [
            'summary' => [
                'orders' => $rows->count(),
                'paid_orders' => $paid->count(),
                'revenue_cents' => (int) $paid->sum('total_cents'),
                'payment_success_percent' => $rows->count() ? round(($paid->count() / $rows->count()) * 100, 1) : 0,
                'failed_orders' => $rows->where('status', Order::STATUS_FAILED)->count(),
                'refund_orders' => $rows->whereIn('status', [Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED])->count(),
                'needs_attention' => $rows->where('needs_attention', true)->count(),
                'average_fulfillment_hours' => $completed->count() ? round((float) $completed->avg('fulfillment_hours'), 1) : 0,
            ],
            'orders' => $rows->values()->all(),
            'payment_statuses' => $this->group($rows, 'status'),
            'fulfillment_statuses' => $this->group($rows, 'fulfillment_status'),
            'providers' => $this->group($rows, 'payment_provider'),
            'attention' => $rows->where('needs_attention', true)->take(15)->values()->all(),
            'timeline' => $this->timeline($period, $rows),
        ];
    }

    public function detail(Order $order, AnalyticsPeriod $period): array
    {
        $row = $this->orderRows($period, ['order_id' => $order->id])->first();
        $order->load(['user:id,name,email', 'items', 'fulfillmentEvents.user:id,name', 'financialTransactions']);

        return [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'fulfillment_status' => $order->fulfillment_status?->value ?? (string) $order->fulfillment_status,
                'customer_name' => $order->user?->name,
                'customer_email' => $order->user?->email,
                'total_cents' => $order->total_cents,
                'currency' => $order->currency,
                'payment_provider' => $order->payment_provider,
                'shipping_carrier' => $order->shipping_carrier,
                'tracking_number' => $order->tracking_number,
                'paid_at' => $order->paid_at?->toIso8601String(),
                'shipped_at' => $order->shipped_at?->toIso8601String(),
                'delivered_at' => $order->delivered_at?->toIso8601String(),
                'fulfilled_at' => $order->fulfilled_at?->toIso8601String(),
                'refunded_at' => $order->refunded_at?->toIso8601String(),
                'canceled_at' => $order->canceled_at?->toIso8601String(),
            ],
            'performance' => $row,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->asset_title ?: $item->image_title ?: 'Order item',
                'license_name' => $item->license_name,
                'quantity' => $item->quantity,
                'total_price_cents' => $item->total_price_cents,
                'status' => $item->status,
            ])->all(),
            'fulfillment_events' => $order->fulfillmentEvents->map(fn ($event) => [
                'status' => $event->status,
                'note' => $event->note,
                'actor' => $event->user?->name,
                'created_at' => $event->created_at?->toIso8601String(),
            ])->all(),
            'financial_transactions' => $order->financialTransactions->map(fn ($transaction) => [
                'type' => $transaction->type?->value ?? (string) $transaction->type,
                'status' => $transaction->status?->value ?? (string) $transaction->status,
                'amount_cents' => $transaction->amount_cents,
                'provider' => $transaction->provider,
                'occurred_at' => $transaction->occurred_at?->toIso8601String(),
            ])->all(),
        ];
    }

    public function exportRows(AnalyticsPeriod $period, array $filters = []): array
    {
        return $this->orderRows($period, $filters)->map(fn (array $row) => [
            $row['order_id'], $row['order_number'], $row['customer_email'], $row['status'],
            $row['fulfillment_status'], $row['payment_provider'], $row['total_cents'],
            $row['paid_at'], $row['fulfilled_at'], $row['fulfillment_hours'], $row['needs_attention'] ? 'Yes' : 'No',
        ])->all();
    }

    private function orderRows(AnalyticsPeriod $period, array $filters = []): Collection
    {
        $query = Order::query()->with('user:id,name,email')
            ->whereBetween('created_at', [$period->start, $period->end]);

        if (! empty($filters['order_id'])) $query->whereKey($filters['order_id']);
        if (! empty($filters['status'])) $query->where('status', $filters['status']);
        if (! empty($filters['fulfillment_status'])) $query->where('fulfillment_status', $filters['fulfillment_status']);
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn ($q) => $q->where('order_number', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%")));
        }

        return $query->latest()->get()->map(function (Order $order): array {
            $fulfillment = $order->fulfilled_at ?: $order->delivered_at;
            $fulfillmentHours = ($order->paid_at && $fulfillment) ? round($order->paid_at->diffInMinutes($fulfillment) / 60, 1) : null;
            $ageHours = $order->created_at?->diffInHours(now()) ?? 0;
            $fulfillmentStatus = $order->fulfillment_status?->value ?? (string) $order->fulfillment_status;
            $needsAttention = $order->status === Order::STATUS_FAILED
                || in_array($order->status, [Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED], true)
                || ($order->status === Order::STATUS_PAID && ! in_array($fulfillmentStatus, ['delivered', 'fulfilled', 'canceled'], true) && $ageHours >= 24);

            return [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->user?->name,
                'customer_email' => $order->user?->email,
                'status' => $order->status,
                'fulfillment_status' => $fulfillmentStatus ?: 'new',
                'payment_provider' => $order->payment_provider ?: 'unspecified',
                'total_cents' => (int) $order->total_cents,
                'paid_at' => $order->paid_at?->toIso8601String(),
                'shipped_at' => $order->shipped_at?->toIso8601String(),
                'delivered_at' => $order->delivered_at?->toIso8601String(),
                'fulfilled_at' => $order->fulfilled_at?->toIso8601String(),
                'fulfillment_hours' => $fulfillmentHours,
                'age_hours' => $ageHours,
                'needs_attention' => $needsAttention,
            ];
        });
    }

    private function group(Collection $rows, string $key): array
    {
        return $rows->groupBy($key)->map(fn (Collection $group, $label) => [
            'label' => str((string) $label)->replace('_', ' ')->title()->toString(),
            'orders' => $group->count(),
            'units' => $group->count(),
            'revenue_cents' => (int) $group->where('status', Order::STATUS_PAID)->sum('total_cents'),
        ])->sortByDesc('orders')->values()->all();
    }

    private function timeline(AnalyticsPeriod $period, Collection $rows): array
    {
        $byDate = $rows->groupBy(fn (array $row) => substr((string) ($row['paid_at'] ?: ''), 0, 10));
        $timeline = [];
        for ($date = $period->start->startOfDay(); $date->lte($period->end); $date = $date->addDay()) {
            $key = $date->toDateString();
            $day = $byDate->get($key, collect());
            $timeline[] = ['date' => $key, 'label' => $date->format('M j'), 'orders' => $day->count(), 'revenue_cents' => (int) $day->sum('total_cents')];
        }
        return $timeline;
    }
}

<?php

namespace App\Analytics;

use App\Enums\FinancialTransactionStatus;
use App\Enums\FinancialTransactionType;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialReportingService
{
    public function report(AnalyticsPeriod $period): array
    {
        $orders = $this->paidOrders($period);
        $refunds = $this->refunds($period);
        $gross = (int) $orders->sum('subtotal_cents');
        $discounts = (int) $orders->sum('discount_cents');
        $tax = (int) $orders->sum('tax_cents');
        $collected = (int) $orders->sum('total_cents');
        $refunded = (int) $refunds->sum('amount_cents');

        return [
            'summary' => [
                'gross_sales_cents' => $gross,
                'discounts_cents' => $discounts,
                'tax_collected_cents' => $tax,
                'collected_revenue_cents' => $collected,
                'refunds_cents' => $refunded,
                'net_revenue_cents' => max(0, $collected - $refunded),
                'paid_orders' => $orders->count(),
                'average_order_value_cents' => $orders->count() ? (int) round($collected / $orders->count()) : 0,
                'units_sold' => (int) OrderItem::query()->whereIn('order_id', $orders->pluck('id'))->sum('quantity'),
            ],
            'daily' => $this->daily($period),
            'licenses' => $this->breakdown($period, "COALESCE(order_items.license_name, 'Unspecified')"),
            'assets' => $this->breakdown($period, "COALESCE(order_items.asset_title, order_items.image_title, 'Unspecified')", 10),
            'providers' => $orders->groupBy(fn (Order $order) => $order->payment_provider ?: 'unknown')->map(fn (Collection $group, string $label) => [
                'label' => ucfirst($label), 'orders' => $group->count(), 'revenue_cents' => (int) $group->sum('total_cents'),
            ])->values()->all(),
            'orders' => $orders->sortByDesc('paid_at')->take(100)->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer' => $order->user?->name ?? 'Guest',
                'provider' => $order->payment_provider ?: 'Unknown',
                'subtotal_cents' => $order->subtotal_cents,
                'discount_cents' => $order->discount_cents,
                'tax_cents' => $order->tax_cents,
                'total_cents' => $order->total_cents,
                'refunded_cents' => (int) $order->financialTransactions->where('type', FinancialTransactionType::Refund)->where('status', FinancialTransactionStatus::Succeeded)->sum('amount_cents'),
                'paid_at' => $order->paid_at?->toIso8601String(),
            ])->values()->all(),
            'reconciliation' => $this->reconciliation($period, $orders, $refunds),
        ];
    }

    public function exportRows(AnalyticsPeriod $period): array
    {
        return collect($this->report($period)['orders'])->map(fn (array $row) => [
            $row['order_number'], $row['paid_at'], $row['customer'], $row['provider'],
            $row['subtotal_cents'], $row['discount_cents'], $row['tax_cents'],
            $row['total_cents'], $row['refunded_cents'], $row['total_cents'] - $row['refunded_cents'],
        ])->all();
    }

    private function paidOrders(AnalyticsPeriod $period): Collection
    {
        return Order::query()->with(['user:id,name', 'financialTransactions'])
            ->where('status', Order::STATUS_PAID)
            ->whereBetween('paid_at', [$period->start, $period->end])->get();
    }

    private function refunds(AnalyticsPeriod $period): Collection
    {
        return FinancialTransaction::query()->where('type', FinancialTransactionType::Refund)
            ->where('status', FinancialTransactionStatus::Succeeded)
            ->whereBetween('occurred_at', [$period->start, $period->end])->get();
    }

    private function daily(AnalyticsPeriod $period): array
    {
        $sales = Order::query()->where('status', Order::STATUS_PAID)->whereBetween('paid_at', [$period->start, $period->end])
            ->selectRaw('DATE(paid_at) metric_date, SUM(total_cents) collected_cents')->groupByRaw('DATE(paid_at)')->pluck('collected_cents', 'metric_date');
        $refunds = FinancialTransaction::query()->where('type', FinancialTransactionType::Refund)->where('status', FinancialTransactionStatus::Succeeded)
            ->whereBetween('occurred_at', [$period->start, $period->end])->selectRaw('DATE(occurred_at) metric_date, SUM(amount_cents) refunded_cents')
            ->groupByRaw('DATE(occurred_at)')->pluck('refunded_cents', 'metric_date');
        $rows = [];
        for ($date = $period->start->startOfDay(); $date->lte($period->end); $date = $date->addDay()) {
            $key = $date->toDateString();
            $collected = (int) ($sales[$key] ?? 0); $refunded = (int) ($refunds[$key] ?? 0);
            $rows[] = ['date' => $key, 'label' => $date->format('M j'), 'collected_cents' => $collected, 'refunded_cents' => $refunded, 'net_cents' => $collected - $refunded];
        }
        return $rows;
    }

    private function breakdown(AnalyticsPeriod $period, string $labelExpression, int $limit = 20): array
    {
        return OrderItem::query()->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', Order::STATUS_PAID)->whereBetween('orders.paid_at', [$period->start, $period->end])
            ->selectRaw("{$labelExpression} as label, SUM(order_items.quantity) units, SUM(order_items.total_price_cents) revenue_cents")
            ->groupBy('label')->orderByDesc('revenue_cents')->limit($limit)->get()->map(fn ($row) => [
                'label' => $row->label, 'units' => (int) $row->units, 'revenue_cents' => (int) $row->revenue_cents,
            ])->all();
    }

    private function reconciliation(AnalyticsPeriod $period, Collection $orders, Collection $refunds): array
    {
        $paidWithoutReference = $orders->filter(fn (Order $order) => blank($order->payment_reference) && blank($order->stripe_payment_intent_id))->count();
        $statusRefundsWithoutLedger = Order::query()->whereIn('status', [Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED])
            ->whereBetween('refunded_at', [$period->start, $period->end])->whereDoesntHave('financialTransactions', fn (Builder $query) => $query->where('type', FinancialTransactionType::Refund))->count();
        return [
            'paid_orders_without_payment_reference' => $paidWithoutReference,
            'refund_status_without_ledger' => $statusRefundsWithoutLedger,
            'failed_financial_transactions' => FinancialTransaction::query()->where('status', FinancialTransactionStatus::Failed)->whereBetween('occurred_at', [$period->start, $period->end])->count(),
            'is_reconciled' => $paidWithoutReference === 0 && $statusRefundsWithoutLedger === 0,
        ];
    }
}

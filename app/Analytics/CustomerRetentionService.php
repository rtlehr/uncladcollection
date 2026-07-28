<?php

namespace App\Analytics;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\Download;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\WishListItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerRetentionService
{
    public function report(AnalyticsPeriod $period): array
    {
        $paidOrders = Order::query()
            ->where('status', Order::STATUS_PAID)
            ->whereBetween('paid_at', [$period->start, $period->end]);

        $buyerIds = (clone $paidOrders)->whereNotNull('user_id')->distinct()->pluck('user_id');
        $repeatBuyerIds = $buyerIds->filter(fn (int $userId): bool => Order::query()
            ->where('user_id', $userId)
            ->where('status', Order::STATUS_PAID)
            ->where('paid_at', '<', $period->start)
            ->exists());

        $notificationGenerated = $this->eventCount(AnalyticsEventName::CustomerNotificationGenerated, $period);
        $notificationOpened = $this->eventCount(AnalyticsEventName::CustomerNotificationOpened, $period);
        $accountVisits = $this->eventCount(AnalyticsEventName::AccountDashboardViewed, $period);
        $documentDownloads = $this->eventCount(AnalyticsEventName::LicenseDocumentDownloaded, $period);

        $downloadGroups = Download::query()
            ->whereBetween('downloaded_at', [$period->start, $period->end])
            ->whereNotNull('user_id')
            ->whereNotNull('license_id')
            ->selectRaw('user_id, license_id, COUNT(*) as download_count')
            ->groupBy('user_id', 'license_id')
            ->get();

        $wishListConversions = $this->wishListConversions($period);
        $wishListSaves = WishListItem::query()->whereBetween('created_at', [$period->start, $period->end])->count();

        return [
            'summary' => [
                'buyers' => $buyerIds->count(),
                'repeat_buyers' => $repeatBuyerIds->count(),
                'repeat_purchase_rate' => $buyerIds->isNotEmpty() ? round(($repeatBuyerIds->count() / $buyerIds->count()) * 100, 1) : 0,
                'account_visits' => $accountVisits,
                're_download_customers' => $downloadGroups->where('download_count', '>', 1)->pluck('user_id')->unique()->count(),
                'license_document_downloads' => $documentDownloads,
                'wish_list_saves' => $wishListSaves,
                'wish_list_conversions' => $wishListConversions->count(),
                'wish_list_conversion_rate' => $wishListSaves > 0 ? round(($wishListConversions->count() / $wishListSaves) * 100, 1) : 0,
                'notifications_generated' => $notificationGenerated,
                'notifications_opened' => $notificationOpened,
                'notification_open_rate' => $notificationGenerated > 0 ? round(($notificationOpened / $notificationGenerated) * 100, 1) : 0,
            ],
            'repeat_buyers' => $this->repeatBuyerRows($repeatBuyerIds, $period),
            'wish_list_conversions' => $wishListConversions->take(20)->values()->all(),
            'daily' => $this->dailySeries($period),
        ];
    }

    private function eventCount(AnalyticsEventName $event, AnalyticsPeriod $period): int
    {
        return AnalyticsEvent::query()
            ->where('event_name', $event->value)
            ->whereBetween('occurred_at', [$period->start, $period->end])
            ->count();
    }

    private function repeatBuyerRows(Collection $ids, AnalyticsPeriod $period): array
    {
        if ($ids->isEmpty()) return [];

        $orders = Order::query()
            ->whereIn('user_id', $ids)
            ->where('status', Order::STATUS_PAID)
            ->whereBetween('paid_at', [$period->start, $period->end])
            ->selectRaw('user_id, COUNT(*) as orders_count, SUM(total_cents) as revenue_cents, MAX(paid_at) as last_purchase_at')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        return User::query()->whereIn('id', $ids)->get(['id', 'name', 'email'])->map(function (User $user) use ($orders): array {
            $row = $orders->get($user->id);
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'orders_count' => (int) ($row?->orders_count ?? 0),
                'revenue_cents' => (int) ($row?->revenue_cents ?? 0),
                'last_purchase_at' => $row?->last_purchase_at,
            ];
        })->sortByDesc('revenue_cents')->take(20)->values()->all();
    }

    private function wishListConversions(AnalyticsPeriod $period): Collection
    {
        return DB::table('wish_list_items as wli')
            ->join('wish_lists as wl', 'wl.id', '=', 'wli.wish_list_id')
            ->join('orders as o', function ($join): void {
                $join->on('o.user_id', '=', 'wl.user_id')->where('o.status', '=', Order::STATUS_PAID);
            })
            ->join('order_items as oi', function ($join): void {
                $join->on('oi.order_id', '=', 'o.id')->on('oi.asset_id', '=', 'wli.asset_id');
            })
            ->join('assets as a', 'a.id', '=', 'wli.asset_id')
            ->join('users as u', 'u.id', '=', 'wl.user_id')
            ->whereBetween('o.paid_at', [$period->start, $period->end])
            ->whereColumn('wli.created_at', '<=', 'o.paid_at')
            ->selectRaw('u.id as user_id, u.name, u.email, a.id as asset_id, a.title as asset_title, MIN(wli.created_at) as saved_at, MIN(o.paid_at) as purchased_at, SUM(oi.total_price_cents) as revenue_cents')
            ->groupBy('u.id', 'u.name', 'u.email', 'a.id', 'a.title')
            ->orderByDesc('purchased_at')
            ->get()
            ->map(fn ($row): array => [
                'user_id' => (int) $row->user_id,
                'name' => $row->name,
                'email' => $row->email,
                'asset_id' => (int) $row->asset_id,
                'asset_title' => $row->asset_title,
                'saved_at' => $row->saved_at,
                'purchased_at' => $row->purchased_at,
                'revenue_cents' => (int) $row->revenue_cents,
            ]);
    }

    private function dailySeries(AnalyticsPeriod $period): array
    {
        $rows = [];
        for ($date = $period->start->startOfDay(); $date->lte($period->end); $date = $date->addDay()) {
            $start = $date->startOfDay();
            $end = $date->endOfDay();
            $rows[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('M j'),
                'account_visits' => AnalyticsEvent::query()->where('event_name', AnalyticsEventName::AccountDashboardViewed->value)->whereBetween('occurred_at', [$start, $end])->count(),
                'notification_opens' => AnalyticsEvent::query()->where('event_name', AnalyticsEventName::CustomerNotificationOpened->value)->whereBetween('occurred_at', [$start, $end])->count(),
                'paid_orders' => Order::query()->where('status', Order::STATUS_PAID)->whereBetween('paid_at', [$start, $end])->count(),
            ];
        }
        return $rows;
    }
}

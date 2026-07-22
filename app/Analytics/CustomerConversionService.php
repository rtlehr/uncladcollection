<?php

namespace App\Analytics;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\CartItem;
use App\Models\Download;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerConversionService
{
    public function report(AnalyticsPeriod $period, array $filters = []): array
    {
        $customers = $this->customerRows($period, $filters);
        $paidOrders = Order::query()->where('status', Order::STATUS_PAID)->whereBetween('paid_at', [$period->start, $period->end]);
        $orders = (clone $paidOrders)->count();
        $buyers = (clone $paidOrders)->distinct('user_id')->count('user_id');
        $revenue = (int) (clone $paidOrders)->sum('total_cents');
        $newCustomers = $customers->where('segment', 'first_time')->count();
        $repeatCustomers = $customers->where('segment', 'repeat')->count();
        $views = AnalyticsEvent::query()->where('event_name', AnalyticsEventName::AssetViewed->value)->whereBetween('occurred_at', [$period->start, $period->end])->count();
        $favorites = AnalyticsEvent::query()->where('event_name', AnalyticsEventName::AssetFavorited->value)->whereBetween('occurred_at', [$period->start, $period->end])->count();
        $cartAdds = AnalyticsEvent::query()->where('event_name', AnalyticsEventName::AssetAddedToCart->value)->whereBetween('occurred_at', [$period->start, $period->end])->count();
        $checkoutStarts = AnalyticsEvent::query()->where('event_name', AnalyticsEventName::CheckoutStarted->value)->whereBetween('occurred_at', [$period->start, $period->end])->count();
        $downloads = Download::query()->whereBetween('downloaded_at', [$period->start, $period->end])->count();
        $abandoned = CartItem::query()->where('updated_at', '<=', now()->subHours(24))->get();

        return [
            'summary' => [
                'buyers' => $buyers,
                'new_customers' => $newCustomers,
                'repeat_customers' => $repeatCustomers,
                'repeat_customer_percent' => $buyers > 0 ? round(($repeatCustomers / $buyers) * 100, 1) : 0,
                'paid_orders' => $orders,
                'revenue_cents' => $revenue,
                'average_customer_value_cents' => $buyers > 0 ? (int) round($revenue / $buyers) : 0,
                'orders_per_customer' => $buyers > 0 ? round($orders / $buyers, 2) : 0,
                'abandoned_cart_lines' => $abandoned->count(),
                'abandoned_cart_value_cents' => (int) $abandoned->sum(fn (CartItem $item) => $item->line_total_cents ?: (($item->price_cents ?: 0) * max(1, $item->quantity))),
            ],
            'funnel' => $this->funnel($views, $favorites, $cartAdds, $checkoutStarts, $orders, $downloads),
            'segments' => [
                ['label' => 'First-time customers', 'customers' => $newCustomers, 'orders' => (int) $customers->where('segment', 'first_time')->sum('period_orders'), 'revenue_cents' => (int) $customers->where('segment', 'first_time')->sum('period_revenue_cents')],
                ['label' => 'Repeat customers', 'customers' => $repeatCustomers, 'orders' => (int) $customers->where('segment', 'repeat')->sum('period_orders'), 'revenue_cents' => (int) $customers->where('segment', 'repeat')->sum('period_revenue_cents')],
            ],
            'customers' => $customers->values()->all(),
            'abandoned_carts' => $this->abandonedCarts(),
            'license_preferences' => $this->licensePreferences($period),
            'media_preferences' => $this->mediaPreferences($period),
        ];
    }

    public function detail(User $user, AnalyticsPeriod $period): array
    {
        $row = $this->customerRows($period, ['user_id' => $user->id])->first();
        $orders = Order::query()->where('user_id', $user->id)->where('status', Order::STATUS_PAID)->latest('paid_at')->limit(25)->get();
        $timeline = [];
        for ($date = $period->start->startOfDay(); $date->lte($period->end); $date = $date->addDay()) {
            $start = $date->startOfDay(); $end = $date->endOfDay();
            $timeline[] = [
                'date' => $date->toDateString(), 'label' => $date->format('M j'),
                'views' => AnalyticsEvent::query()->where('user_id', $user->id)->where('event_name', AnalyticsEventName::AssetViewed->value)->whereBetween('occurred_at', [$start, $end])->count(),
                'cart_additions' => AnalyticsEvent::query()->where('user_id', $user->id)->where('event_name', AnalyticsEventName::AssetAddedToCart->value)->whereBetween('occurred_at', [$start, $end])->count(),
                'orders' => Order::query()->where('user_id', $user->id)->where('status', Order::STATUS_PAID)->whereBetween('paid_at', [$start, $end])->count(),
                'revenue_cents' => (int) Order::query()->where('user_id', $user->id)->where('status', Order::STATUS_PAID)->whereBetween('paid_at', [$start, $end])->sum('total_cents'),
            ];
        }

        return [
            'customer' => ['id' => $user->id, 'name' => $user->name, 'username' => $user->username, 'email' => $user->email, 'created_at' => $user->created_at?->toIso8601String()],
            'performance' => $row,
            'timeline' => $timeline,
            'orders' => $orders->map(fn (Order $order) => ['id' => $order->id, 'order_number' => $order->order_number, 'paid_at' => $order->paid_at?->toIso8601String(), 'total_cents' => (int) $order->total_cents])->all(),
            'active_cart' => CartItem::query()->where('user_id', $user->id)->with('asset:id,title')->get()->map(fn (CartItem $item) => ['id' => $item->id, 'asset_title' => $item->asset?->title, 'quantity' => (int) $item->quantity, 'value_cents' => (int) ($item->line_total_cents ?: $item->price_cents), 'updated_at' => $item->updated_at?->toIso8601String()])->all(),
        ];
    }

    public function exportRows(AnalyticsPeriod $period, array $filters = []): array
    {
        return $this->customerRows($period, $filters)->map(fn (array $row) => [$row['customer_id'], $row['name'], $row['email'], $row['segment'], $row['period_orders'], $row['period_revenue_cents'], $row['lifetime_orders'], $row['lifetime_revenue_cents'], $row['downloads'], $row['last_purchase_at']])->all();
    }

    private function customerRows(AnalyticsPeriod $period, array $filters = []): Collection
    {
        $periodOrders = Order::query()->where('status', Order::STATUS_PAID)->whereBetween('paid_at', [$period->start, $period->end])
            ->selectRaw('user_id, COUNT(*) period_orders, SUM(total_cents) period_revenue_cents, MIN(paid_at) first_period_purchase_at, MAX(paid_at) last_purchase_at')->groupBy('user_id')->get()->keyBy('user_id');
        $lifetime = Order::query()->where('status', Order::STATUS_PAID)
            ->selectRaw('user_id, COUNT(*) lifetime_orders, SUM(total_cents) lifetime_revenue_cents, MIN(paid_at) first_purchase_at, MAX(paid_at) last_purchase_at')->groupBy('user_id')->get()->keyBy('user_id');
        $priorBuyers = Order::query()->where('status', Order::STATUS_PAID)->where('paid_at', '<', $period->start)->pluck('user_id')->flip();
        $downloads = Download::query()->whereBetween('downloaded_at', [$period->start, $period->end])->selectRaw('user_id, COUNT(*) aggregate')->groupBy('user_id')->pluck('aggregate', 'user_id');
        $views = AnalyticsEvent::query()->where('event_name', AnalyticsEventName::AssetViewed->value)->whereBetween('occurred_at', [$period->start, $period->end])->whereNotNull('user_id')->selectRaw('user_id, COUNT(*) aggregate')->groupBy('user_id')->pluck('aggregate', 'user_id');
        $cartLines = CartItem::query()->selectRaw('user_id, COUNT(*) cart_line_count, SUM(COALESCE(line_total_cents, price_cents, 0)) value_cents')->groupBy('user_id')->get()->keyBy('user_id');

        $query = User::query()->whereIn('id', $periodOrders->keys());
        if (!empty($filters['user_id'])) $query->whereKey($filters['user_id']);
        if (!empty($filters['search'])) $query->where(fn ($q) => $q->where('name', 'like', '%'.$filters['search'].'%')->orWhere('email', 'like', '%'.$filters['search'].'%')->orWhere('username', 'like', '%'.$filters['search'].'%'));
        if (!empty($filters['segment']) && $filters['segment'] !== 'all') {
            $ids = $periodOrders->keys()->filter(fn ($id) => ($filters['segment'] === 'repeat') === $priorBuyers->has($id));
            $query->whereIn('id', $ids);
        }

        return $query->get()->map(function (User $user) use ($periodOrders, $lifetime, $priorBuyers, $downloads, $views, $cartLines): array {
            $periodRow = $periodOrders->get($user->id); $life = $lifetime->get($user->id); $cart = $cartLines->get($user->id);
            return [
                'customer_id' => $user->id, 'name' => $user->name, 'username' => $user->username, 'email' => $user->email,
                'segment' => $priorBuyers->has($user->id) ? 'repeat' : 'first_time',
                'period_orders' => (int) ($periodRow?->period_orders ?? 0), 'period_revenue_cents' => (int) ($periodRow?->period_revenue_cents ?? 0),
                'lifetime_orders' => (int) ($life?->lifetime_orders ?? 0), 'lifetime_revenue_cents' => (int) ($life?->lifetime_revenue_cents ?? 0),
                'average_order_value_cents' => ($periodRow?->period_orders ?? 0) > 0 ? (int) round($periodRow->period_revenue_cents / $periodRow->period_orders) : 0,
                'views' => (int) ($views[$user->id] ?? 0), 'downloads' => (int) ($downloads[$user->id] ?? 0),
                'active_cart_lines' => (int) ($cart?->cart_line_count ?? 0), 'active_cart_value_cents' => (int) ($cart?->value_cents ?? 0),
                'first_purchase_at' => $life?->first_purchase_at, 'last_purchase_at' => $life?->last_purchase_at,
            ];
        })->sortByDesc(fn (array $row) => [$row['period_revenue_cents'], $row['period_orders']])->values();
    }

    private function funnel(int $views, int $favorites, int $carts, int $checkouts, int $orders, int $downloads): array
    {
        $values = [['views','Asset views',$views],['favorites','Favorites',$favorites],['cart','Cart additions',$carts],['checkout','Checkout starts',$checkouts],['orders','Paid orders',$orders],['downloads','Downloads',$downloads]];
        return collect($values)->map(function ($stage, $index) use ($values) { $previous = $index === 0 ? 0 : $values[$index - 1][2]; return ['key'=>$stage[0],'label'=>$stage[1],'value'=>$stage[2],'conversion_percent'=>$index === 0 ? 100 : ($previous > 0 ? round(($stage[2]/$previous)*100,1) : 0)]; })->all();
    }

    private function abandonedCarts(): array
    {
        return CartItem::query()->where('updated_at', '<=', now()->subHours(24))->with(['user:id,name,email','asset:id,title'])->latest('updated_at')->limit(25)->get()->map(fn (CartItem $item) => ['cart_item_id'=>$item->id,'customer_id'=>$item->user_id,'customer_name'=>$item->user?->name,'customer_email'=>$item->user?->email,'asset_title'=>$item->asset?->title,'quantity'=>(int)$item->quantity,'value_cents'=>(int)($item->line_total_cents ?: $item->price_cents),'updated_at'=>$item->updated_at?->toIso8601String()])->all();
    }

    private function licensePreferences(AnalyticsPeriod $period): array
    {
        return OrderItem::query()->join('orders','orders.id','=','order_items.order_id')->where('orders.status',Order::STATUS_PAID)->whereBetween('orders.paid_at',[$period->start,$period->end])->selectRaw("COALESCE(order_items.license_name,'Unspecified') label, COUNT(DISTINCT orders.user_id) customers, SUM(order_items.quantity) units, SUM(order_items.total_price_cents) revenue_cents")->groupBy('label')->orderByDesc('revenue_cents')->get()->map(fn($r)=>['label'=>$r->label,'customers'=>(int)$r->customers,'units'=>(int)$r->units,'revenue_cents'=>(int)$r->revenue_cents])->all();
    }

    private function mediaPreferences(AnalyticsPeriod $period): array
    {
        return OrderItem::query()->join('orders','orders.id','=','order_items.order_id')->leftJoin('assets','assets.id','=','order_items.asset_id')->where('orders.status',Order::STATUS_PAID)->whereBetween('orders.paid_at',[$period->start,$period->end])->selectRaw("COALESCE(assets.asset_type,'legacy_image') label, COUNT(DISTINCT orders.user_id) customers, SUM(order_items.quantity) units, SUM(order_items.total_price_cents) revenue_cents")->groupBy('label')->orderByDesc('revenue_cents')->get()->map(fn($r)=>['label'=>$r->label instanceof \BackedEnum ? $r->label->value : (string)$r->label,'customers'=>(int)$r->customers,'units'=>(int)$r->units,'revenue_cents'=>(int)$r->revenue_cents])->all();
    }
}

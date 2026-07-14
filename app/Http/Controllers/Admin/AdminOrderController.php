<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderFulfillmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class AdminOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $fulfillment = $request->string('fulfillment_status')->toString();
        $sort = $request->string('sort')->toString() ?: 'created_at';
        $direction = in_array($request->string('direction')->toString(), ['asc','desc'], true) ? $request->string('direction')->toString() : 'desc';
        $allowedSorts = ['order_number','status','fulfillment_status','total_cents','paid_at','created_at'];
        if (! in_array($sort, $allowedSorts, true)) $sort = 'created_at';

        $orders = Order::query()->with('user')->withCount(['items','licenses'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('order_number','like',"%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name','like',"%{$search}%")->orWhere('email','like',"%{$search}%"))
                ->orWhereHas('items', fn ($i) => $i->where('image_title','like',"%{$search}%")->orWhere('asset_title','like',"%{$search}%"))))
            ->when($status !== '', fn ($q) => $q->where('status',$status))
            ->when($fulfillment !== '', fn ($q) => $q->where('fulfillment_status',$fulfillment))
            ->orderBy($sort,$direction)->paginate(20)->withQueryString()
            ->through(fn (Order $order) => [
                'id'=>$order->id,'order_number'=>$order->order_number,'status'=>$order->status,
                'fulfillment_status'=>$order->fulfillment_status?->value ?? (string) $order->fulfillment_status,
                'total_formatted'=>$order->total_formatted,'currency'=>$order->currency,
                'payment_provider'=>$order->payment_provider,'paid_at'=>$order->paid_at?->format('Y-m-d H:i'),
                'created_at'=>$order->created_at?->format('Y-m-d H:i'),'items_count'=>$order->items_count,
                'licenses_count'=>$order->licenses_count,
                'user'=>$order->user ? ['id'=>$order->user->id,'name'=>$order->user->name,'email'=>$order->user->email] : null,
            ]);

        return Inertia::render('Admin/Orders/Index', [
            'orders'=>$orders,'filters'=>['search'=>$search,'status'=>$status,'fulfillment_status'=>$fulfillment,'sort'=>$sort,'direction'=>$direction],
            'statuses'=>[Order::STATUS_PENDING,Order::STATUS_PAID,Order::STATUS_FAILED,Order::STATUS_CANCELED,Order::STATUS_REFUNDED,Order::STATUS_PARTIALLY_REFUNDED],
            'fulfillmentStatuses'=>$this->fulfillmentStatuses(),
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['user','items.image','items.asset','items.licenseType','items.assetOffering','licenses.image','licenses.asset','licenses.licenseType','licenses.downloads','fulfillmentEvents.user']);
        return Inertia::render('Admin/Orders/Show', ['order'=>$this->detail($order),'fulfillmentStatuses'=>$this->fulfillmentStatuses()]);
    }

    public function updateFulfillment(Request $request, Order $order, OrderFulfillmentService $service): RedirectResponse
    {
        $validated = $request->validate([
            'fulfillment_status'=>['required','string','in:'.collect(OrderFulfillmentStatus::cases())->pluck('value')->implode(',')],
            'shipping_carrier'=>['nullable','string','max:80'],'tracking_number'=>['nullable','string','max:160'],
            'fulfillment_notes'=>['nullable','string','max:5000'],'event_note'=>['nullable','string','max:1000'],
        ]);
        $service->update($order,$validated,$request->user());
        return back()->with('success','Fulfillment status updated.');
    }

    public function invoice(Order $order): View
    {
        $order->load(['user','items.asset','items.image','items.assetOffering','items.licenseType']);
        return view('admin.orders.invoice', ['order'=>$order]);
    }

    private function fulfillmentStatuses(): array
    {
        return collect(OrderFulfillmentStatus::cases())->map(fn ($s)=>['value'=>$s->value,'label'=>$s->label()])->all();
    }

    private function detail(Order $order): array
    {
        return [
            'id'=>$order->id,'order_number'=>$order->order_number,'status'=>$order->status,
            'fulfillment_status'=>$order->fulfillment_status?->value ?? (string)$order->fulfillment_status,
            'subtotal_formatted'=>$order->subtotal_formatted,'total_formatted'=>$order->total_formatted,
            'subtotal_cents'=>$order->subtotal_cents,'discount_cents'=>$order->discount_cents,'tax_cents'=>$order->tax_cents,'total_cents'=>$order->total_cents,'currency'=>$order->currency,
            'payment_provider'=>$order->payment_provider,'payment_reference'=>$order->payment_reference,'stripe_checkout_session_id'=>$order->stripe_checkout_session_id,'stripe_payment_intent_id'=>$order->stripe_payment_intent_id,
            'shipping_carrier'=>$order->shipping_carrier,'tracking_number'=>$order->tracking_number,'fulfillment_notes'=>$order->fulfillment_notes,
            'paid_at'=>$order->paid_at?->format('Y-m-d H:i'),'refunded_at'=>$order->refunded_at?->format('Y-m-d H:i'),'canceled_at'=>$order->canceled_at?->format('Y-m-d H:i'),'shipped_at'=>$order->shipped_at?->format('Y-m-d H:i'),'delivered_at'=>$order->delivered_at?->format('Y-m-d H:i'),'fulfilled_at'=>$order->fulfilled_at?->format('Y-m-d H:i'),'created_at'=>$order->created_at?->format('Y-m-d H:i'),
            'user'=>$order->user ? ['id'=>$order->user->id,'name'=>$order->user->name,'email'=>$order->user->email] : null,
            'items'=>$order->items->map(fn($item)=>[
                'id'=>$item->id,'status'=>$item->status,'fulfillment_type'=>$item->fulfillment_type,'quantity'=>$item->quantity,
                'unit_price_formatted'=>$item->unit_price_formatted,'total_price_formatted'=>$item->total_price_formatted,
                'title'=>$item->asset_title ?: $item->image_title,'license_name'=>$item->offering_name ?: $item->license_name,
                'configuration'=>$item->configuration_snapshot,'shipping_address'=>$item->shipping_address_snapshot,'pricing'=>$item->pricing_snapshot,'included_files'=>$item->included_asset_files_snapshot,
                'asset'=>$item->asset ? ['id'=>$item->asset->id,'title'=>$item->asset->title,'slug'=>$item->asset->slug] : null,
                'image'=>$item->image ? ['id'=>$item->image->id,'title'=>$item->image->title,'slug'=>$item->image->slug] : null,
            ])->values(),
            'licenses'=>$order->licenses->map(fn($l)=>['id'=>$l->id,'license_key'=>$l->license_key,'status'=>$l->status,'license_name'=>$l->license_name,'downloads_used'=>$l->downloads_used,'download_limit'=>$l->download_limit,'starts_at'=>$l->starts_at?->format('Y-m-d'),'expires_at'=>$l->expires_at?->format('Y-m-d'),'downloads_count'=>$l->downloads->count(),'title'=>$l->asset?->title ?: $l->image?->title])->values(),
            'fulfillment_events'=>$order->fulfillmentEvents->map(fn($e)=>['id'=>$e->id,'status'=>$e->status,'note'=>$e->note,'created_at'=>$e->created_at?->format('Y-m-d H:i'),'user'=>$e->user?->name])->values(),
        ];
    }
}

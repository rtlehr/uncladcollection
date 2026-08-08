<?php

namespace App\Http\Controllers\Admin;

use App\Advertising\AdvertisingWorkflowContextService;
use App\Http\Controllers\Controller;
use App\Models\{AdvertisingCampaign, AdvertisingInvoice, Advertiser};
use App\Services\{AdvertisingBillingService, AdvertisingStripeCheckoutService};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdvertisingInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = AdvertisingInvoice::with(['advertiser', 'campaign'])->latest();
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('advertiser_id')) $query->where('advertiser_id', $request->integer('advertiser_id'));
        if ($request->filled('search')) $query->where(fn ($q) => $q->where('invoice_number', 'like', '%'.$request->search.'%')->orWhereHas('advertiser', fn ($a) => $a->where('name', 'like', '%'.$request->search.'%')));
        return Inertia::render('Admin/Advertising/Billing/Index', ['invoices' => $query->get(), 'filters' => $request->only(['status', 'search', 'advertiser_id'])]);
    }

    public function create(Request $request, AdvertisingWorkflowContextService $context)
    {
        $workflowContext = $context->fromRequest($request);
        $campaign = $workflowContext['campaign'] ? AdvertisingCampaign::find($workflowContext['campaign']['id']) : null;

        return Inertia::render('Admin/Advertising/Billing/Form', [
            'invoice' => null,
            'advertisers' => Advertiser::orderBy('name')->get(['id', 'name']),
            'campaigns' => AdvertisingCampaign::with('advertiser:id,name')->orderByDesc('id')->get(['id', 'advertiser_id', 'name', 'contract_value_cents', 'pricing_model']),
            'workflowContext' => $workflowContext,
            'initialInvoice' => [
                'advertiser_id' => $workflowContext['advertiser']['id'] ?? null,
                'advertising_campaign_id' => $campaign?->id,
                'items' => $campaign ? [[
                    'description' => $campaign->name.' advertising campaign',
                    'billing_model' => $campaign->pricing_model,
                    'quantity' => 1,
                    'unit_amount_cents' => (int) $campaign->contract_value_cents,
                ]] : null,
            ],
        ]);
    }

    public function store(Request $request, AdvertisingBillingService $billing)
    {
        $data = $this->data($request);
        $invoice = AdvertisingInvoice::create(array_merge($data, ['uuid' => (string) Str::uuid(), 'invoice_number' => 'ADV-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)), 'status' => 'draft']));
        foreach ($request->input('items', []) as $item) $invoice->items()->create($this->item($item));
        $billing->recalculate($invoice);
        return to_route('admin.advertising-invoices.show', $invoice)->with('success', 'Advertising invoice created.');
    }

    public function show(AdvertisingInvoice $advertisingInvoice, AdvertisingBillingService $billing)
    {
        $billing->recalculate($advertisingInvoice);
        return Inertia::render('Admin/Advertising/Billing/Show', ['invoice' => $advertisingInvoice->load(['advertiser', 'campaign', 'items', 'payments' => fn ($q) => $q->latest()])]);
    }

    public function edit(AdvertisingInvoice $advertisingInvoice, AdvertisingWorkflowContextService $context)
    {
        abort_if(! in_array($advertisingInvoice->status, ['draft', 'issued', 'overdue']), 422);
        $advertisingInvoice->load(['items', 'advertiser', 'campaign']);
        return Inertia::render('Admin/Advertising/Billing/Form', [
            'invoice' => $advertisingInvoice,
            'advertisers' => Advertiser::orderBy('name')->get(['id', 'name']),
            'campaigns' => AdvertisingCampaign::with('advertiser:id,name')->orderByDesc('id')->get(['id', 'advertiser_id', 'name', 'contract_value_cents', 'pricing_model']),
            'workflowContext' => $context->payload($advertisingInvoice->advertiser, null, $advertisingInvoice->campaign),
            'initialInvoice' => null,
        ]);
    }

    public function update(Request $request, AdvertisingInvoice $advertisingInvoice, AdvertisingBillingService $billing)
    {
        abort_if(in_array($advertisingInvoice->status, ['paid', 'void', 'refunded']), 422);
        $advertisingInvoice->update($this->data($request));
        $advertisingInvoice->items()->delete();
        foreach ($request->input('items', []) as $item) $advertisingInvoice->items()->create($this->item($item));
        $billing->recalculate($advertisingInvoice);
        return to_route('admin.advertising-invoices.show', $advertisingInvoice)->with('success', 'Invoice updated.');
    }

    public function issue(AdvertisingInvoice $advertisingInvoice, AdvertisingBillingService $billing) { abort_unless($advertisingInvoice->status === 'draft', 422); $billing->recalculate($advertisingInvoice); abort_if($advertisingInvoice->total_cents < 1, 422, 'Invoice must have a positive total.'); $advertisingInvoice->update(['status' => 'issued', 'issued_at' => today(), 'due_at' => $advertisingInvoice->due_at ?: today()->addDays(30)]); return back()->with('success', 'Invoice issued.'); }
    public function void(AdvertisingInvoice $advertisingInvoice) { abort_if($advertisingInvoice->paid_cents > 0, 422, 'Paid invoices cannot be voided.'); $advertisingInvoice->update(['status' => 'void', 'voided_at' => now(), 'balance_cents' => 0]); return back()->with('success', 'Invoice voided.'); }
    public function payment(Request $request, AdvertisingInvoice $advertisingInvoice, AdvertisingBillingService $billing) { $data = $request->validate(['amount_cents' => 'required|integer|min:1', 'provider' => 'required|in:manual,check,wire,stripe', 'provider_reference' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:2000']); $billing->record($advertisingInvoice, (int) $data['amount_cents'], $data['provider'], $data['provider_reference'] ?? null, $data['notes'] ?? null); return back()->with('success', 'Payment recorded.'); }
    public function refund(Request $request, AdvertisingInvoice $advertisingInvoice, AdvertisingBillingService $billing) { $data = $request->validate(['amount_cents' => 'required|integer|min:1', 'provider_reference' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:2000']); $billing->record($advertisingInvoice, (int) $data['amount_cents'], 'manual', $data['provider_reference'] ?? null, $data['notes'] ?? null, 'refund'); return back()->with('success', 'Refund recorded.'); }
    public function checkout(AdvertisingInvoice $advertisingInvoice, AdvertisingStripeCheckoutService $stripe) { return Inertia::location($stripe->create($advertisingInvoice)->url); }

    private function data(Request $request): array { return $request->validate(['advertiser_id' => 'required|exists:advertisers,id', 'advertising_campaign_id' => 'nullable|exists:advertising_campaigns,id', 'currency' => 'required|string|size:3', 'discount_cents' => 'required|integer|min:0', 'tax_cents' => 'required|integer|min:0', 'due_at' => 'nullable|date', 'notes' => 'nullable|string|max:5000', 'items' => 'required|array|min:1', 'items.*.description' => 'required|string|max:255', 'items.*.billing_model' => 'required|in:flat,cpm,cpc,sponsorship', 'items.*.quantity' => 'required|integer|min:1', 'items.*.unit_amount_cents' => 'required|integer|min:0']); }
    private function item(array $item): array { $item['line_total_cents'] = (int) $item['quantity'] * (int) $item['unit_amount_cents']; return $item; }
}

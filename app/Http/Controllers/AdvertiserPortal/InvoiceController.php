<?php

namespace App\Http\Controllers\AdvertiserPortal;

use App\Models\AdvertisingInvoice;
use App\Services\AdvertisingStripeCheckoutService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends PortalController
{
    public function index(Request $request)
    {
        abort_unless($this->membership($request)->canViewBilling(),403);
        return Inertia::render('Advertiser/Invoices/Index',['advertiser'=>$this->advertiser($request),'membership'=>$this->membership($request),'invoices'=>$this->advertiser($request)->invoices()->with('campaign')->latest()->get()]);
    }
    public function show(Request $request, AdvertisingInvoice $invoice)
    {
        abort_unless($this->membership($request)->canViewBilling(),403); abort_unless($invoice->advertiser_id===$this->advertiser($request)->id,404);
        return Inertia::render('Advertiser/Invoices/Show',['advertiser'=>$this->advertiser($request),'membership'=>$this->membership($request),'invoice'=>$invoice->load(['campaign','items','payments'=>fn($q)=>$q->latest()])]);
    }
    public function checkout(Request $request, AdvertisingInvoice $invoice, AdvertisingStripeCheckoutService $stripe)
    {
        abort_unless($this->membership($request)->canViewBilling(),403); abort_unless($invoice->advertiser_id===$this->advertiser($request)->id,404);
        return Inertia::location($stripe->create($invoice)->url);
    }
}

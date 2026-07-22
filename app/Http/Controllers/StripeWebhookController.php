<?php

namespace App\Http\Controllers;

use App\Commerce\Checkout\CheckoutEngine;
use App\Models\Order;
use App\Models\AdvertisingPayment;
use App\Services\AdvertisingBillingService;
use App\Models\FinancialTransaction;
use App\Enums\FinancialTransactionType;
use App\Enums\FinancialTransactionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly CheckoutEngine $checkoutEngine, private readonly AdvertisingBillingService $advertisingBilling) {}

    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (! is_string($signature) || $signature === '') {
            return response('Missing signature.', 400);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, config('stripe.webhook_secret'));
        } catch (UnexpectedValueException) {
            return response('Invalid payload.', 400);
        } catch (SignatureVerificationException) {
            return response('Invalid signature.', 400);
        }

        $session = $event->data->object ?? null;

        if (($session->metadata->billing_type ?? null) === 'advertising_invoice') {
            $payment = AdvertisingPayment::query()->where('stripe_checkout_session_id', $session->id ?? null)->first();
            if (! $payment) return response('Webhook handled.', 200);
            if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true) && ($session->payment_status ?? null) === 'paid' && $payment->status !== 'succeeded') {
                if ((int) ($session->amount_total ?? 0) !== (int) $payment->amount_cents) return response('Invoice amount mismatch.', 400);
                $payment->update(['status'=>'succeeded','stripe_payment_intent_id'=>$session->payment_intent ?? null,'provider_reference'=>$session->payment_intent ?? null,'processed_at'=>now()]);
                FinancialTransaction::firstOrCreate(
                    ['advertising_payment_id'=>$payment->id],
                    ['advertising_invoice_id'=>$payment->advertising_invoice_id,'type'=>FinancialTransactionType::Payment,'status'=>FinancialTransactionStatus::Succeeded,'amount_cents'=>$payment->amount_cents,'currency'=>$payment->currency,'provider'=>'stripe','provider_reference'=>$payment->provider_reference,'reason'=>'Advertising invoice payment','occurred_at'=>now()]
                );
                $this->advertisingBilling->recalculate($payment->invoice);
            } elseif (in_array($event->type, ['checkout.session.expired', 'checkout.session.async_payment_failed'], true) && $payment->status === 'pending') {
                $payment->update(['status'=>$event->type === 'checkout.session.expired' ? 'canceled' : 'failed']);
            }
            return response('Webhook handled.', 200);
        }

        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            if (($session->payment_status ?? null) !== 'paid') {
                return response('Webhook handled.', 200);
            }

            $order = Order::query()->where('stripe_checkout_session_id', $session->id)->first();
            if (! $order) {
                return response('Webhook handled.', 200);
            }

            $metadataOrderId = (int) ($session->metadata->order_id ?? 0);
            if ($metadataOrderId > 0 && $metadataOrderId !== $order->id) {
                return response('Order metadata mismatch.', 400);
            }

            $amountTotal = isset($session->amount_total) ? (int) $session->amount_total : null;
            if ($amountTotal !== null && $amountTotal !== (int) $order->total_cents) {
                return response('Order amount mismatch.', 400);
            }

            $order->update([
                'payment_reference' => $session->payment_intent ?? null,
                'stripe_payment_intent_id' => $session->payment_intent ?? null,
            ]);

            $this->checkoutEngine->markPaid($order);
        }

        if (in_array($event->type, ['checkout.session.expired', 'checkout.session.async_payment_failed'], true)) {
            $order = Order::query()->where('stripe_checkout_session_id', $session->id)->first();

            if ($order && $order->status === Order::STATUS_PENDING) {
                $order->update([
                    'status' => $event->type === 'checkout.session.expired'
                        ? Order::STATUS_CANCELED
                        : Order::STATUS_FAILED,
                    'canceled_at' => $event->type === 'checkout.session.expired' ? now() : null,
                ]);
            }
        }

        return response('Webhook handled.', 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();

        $signature = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('stripe.webhook_secret')
            );
        } catch (UnexpectedValueException $e) {
            return response('Invalid payload.', 400);
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature.', 400);
        }

        switch ($event->type) {

            case 'checkout.session.completed':

                $session = $event->data->object;

                $order = Order::query()
                    ->where(
                        'stripe_checkout_session_id',
                        $session->id
                    )
                    ->first();

                if ($order && $order->status !== Order::STATUS_PAID) {

                    $order->update([
                        'payment_reference' => $session->payment_intent ?? null,
                        'stripe_payment_intent_id' => $session->payment_intent ?? null,
                    ]);

                    $this->purchaseService->markOrderPaid($order);
                }

                break;

            case 'checkout.session.expired':

                $session = $event->data->object;

                $order = Order::query()
                    ->where(
                        'stripe_checkout_session_id',
                        $session->id
                    )
                    ->first();

                if ($order && $order->status === Order::STATUS_PENDING) {

                    $order->update([
                        'status' => Order::STATUS_CANCELED,
                        'canceled_at' => now(),
                    ]);
                }

                break;
        }

        return response('Webhook handled.', 200);
    }
}
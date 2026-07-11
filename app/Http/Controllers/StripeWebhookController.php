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
        protected PurchaseService $purchaseService,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (! is_string($signature) || $signature === '') {
            return response('Missing signature.', 400);
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('stripe.webhook_secret'),
            );
        } catch (UnexpectedValueException) {
            return response('Invalid payload.', 400);
        } catch (SignatureVerificationException) {
            return response('Invalid signature.', 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                $session = $event->data->object;

                if (($session->payment_status ?? null) !== 'paid') {
                    break;
                }

                $order = Order::query()
                    ->where(
                        'stripe_checkout_session_id',
                        $session->id,
                    )
                    ->first();

                if (! $order) {
                    break;
                }

                $metadataOrderId = (int) ($session->metadata->order_id ?? 0);

                if (
                    $metadataOrderId > 0
                    && $metadataOrderId !== $order->id
                ) {
                    return response('Order metadata mismatch.', 400);
                }

                $order->update([
                    'payment_reference' => $session->payment_intent ?? null,
                    'stripe_payment_intent_id' => $session->payment_intent ?? null,
                ]);

                $this->purchaseService->markOrderPaid($order);

                break;

            case 'checkout.session.expired':
            case 'checkout.session.async_payment_failed':
                $session = $event->data->object;

                $order = Order::query()
                    ->where(
                        'stripe_checkout_session_id',
                        $session->id,
                    )
                    ->first();

                if (
                    $order
                    && $order->status === Order::STATUS_PENDING
                ) {
                    $order->update([
                        'status' => $event->type === 'checkout.session.expired'
                            ? Order::STATUS_CANCELED
                            : Order::STATUS_FAILED,
                        'canceled_at' => $event->type === 'checkout.session.expired'
                            ? now()
                            : null,
                    ]);
                }

                break;
        }

        return response('Webhook handled.', 200);
    }
}

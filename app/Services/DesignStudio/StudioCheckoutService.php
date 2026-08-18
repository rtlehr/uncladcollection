<?php

namespace App\Services\DesignStudio;

use App\Models\DesignProject;
use App\Models\StudioCreditPackage;
use App\Models\StudioCreditTransaction;
use App\Models\User;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StudioCheckoutService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret'));
    }

    public function create(User $user, DesignProject $project, StudioCreditPackage $package): Session
    {
        abort_unless($package->is_active, 404, 'This Studio credit package is unavailable.');

        $transaction = StudioCreditTransaction::create([
            'user_id' => $user->id,
            'studio_credit_package_id' => $package->id,
            'type' => StudioCreditTransaction::TYPE_PURCHASE,
            'status' => StudioCreditTransaction::STATUS_PENDING,
            'credits' => $package->credits,
            'amount_cents' => $package->price_cents,
            'currency' => strtoupper($package->currency),
            'metadata' => ['design_project_id' => $project->id],
        ]);

        try {
            $session = Session::create([
                'mode' => 'payment',
                'customer_email' => $user->email,
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($package->currency),
                        'unit_amount' => $package->price_cents,
                        'product_data' => [
                            'name' => $package->name,
                            'description' => $package->description ?: $package->credits.' Creative Studio export credits',
                        ],
                    ],
                ]],
                'success_url' => route('account.designs.edit', $project).'?studio_purchase=success',
                'cancel_url' => route('account.designs.edit', $project).'?studio_purchase=canceled',
                'metadata' => [
                    'billing_type' => 'studio_credits',
                    'studio_credit_transaction_id' => (string) $transaction->id,
                    'studio_credit_package_id' => (string) $package->id,
                    'user_id' => (string) $user->id,
                    'design_project_id' => (string) $project->id,
                ],
            ], [
                'idempotency_key' => 'studio-'.hash('sha256', app()->environment().'|'.$transaction->uuid),
            ]);
        } catch (\Throwable $exception) {
            $transaction->update(['status' => StudioCreditTransaction::STATUS_VOID, 'voided_at' => now()]);
            throw $exception;
        }

        $transaction->update(['stripe_checkout_session_id' => $session->id]);

        return $session;
    }

    public function completeStripePurchase(object $session): bool
    {
        $transactionId = (int) ($session->metadata->studio_credit_transaction_id ?? 0);
        $transaction = StudioCreditTransaction::query()->with('package')->find($transactionId);

        if (! $transaction || $transaction->type !== StudioCreditTransaction::TYPE_PURCHASE) {
            return false;
        }

        if ($transaction->status === StudioCreditTransaction::STATUS_POSTED) {
            return true;
        }

        if (($session->payment_status ?? null) !== 'paid') {
            return false;
        }

        if ((int) ($session->amount_total ?? -1) !== (int) $transaction->amount_cents) {
            throw new \RuntimeException('Studio credit purchase amount mismatch.');
        }

        if ($transaction->stripe_checkout_session_id && $transaction->stripe_checkout_session_id !== ($session->id ?? null)) {
            throw new \RuntimeException('Studio credit checkout session mismatch.');
        }

        $transaction->forceFill([
            'status' => StudioCreditTransaction::STATUS_POSTED,
            'stripe_checkout_session_id' => $session->id ?? $transaction->stripe_checkout_session_id,
            'stripe_payment_intent_id' => $session->payment_intent ?? null,
            'posted_at' => now(),
            'voided_at' => null,
        ])->save();

        return true;
    }

    public function failStripePurchase(object $session): void
    {
        $transactionId = (int) ($session->metadata->studio_credit_transaction_id ?? 0);
        StudioCreditTransaction::query()
            ->whereKey($transactionId)
            ->where('type', StudioCreditTransaction::TYPE_PURCHASE)
            ->where('status', StudioCreditTransaction::STATUS_PENDING)
            ->update(['status' => StudioCreditTransaction::STATUS_VOID, 'voided_at' => now(), 'updated_at' => now()]);
    }
}

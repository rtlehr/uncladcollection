<?php

namespace App\Services\DesignStudio;

use App\Models\DesignExport;
use App\Models\License;
use App\Models\StudioCreditTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudioCreditService
{
    public function balance(User|int $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;

        return (int) StudioCreditTransaction::query()
            ->where('user_id', $userId)
            ->where('status', StudioCreditTransaction::STATUS_POSTED)
            ->sum('credits');
    }

    public function availableBalance(User|int $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;

        $posted = (int) StudioCreditTransaction::query()
            ->where('user_id', $userId)
            ->where('status', StudioCreditTransaction::STATUS_POSTED)
            ->sum('credits');

        $reserved = (int) StudioCreditTransaction::query()
            ->where('user_id', $userId)
            ->where('status', StudioCreditTransaction::STATUS_PENDING)
            ->where('type', StudioCreditTransaction::TYPE_EXPORT)
            ->sum('credits');

        return max(0, $posted + $reserved);
    }

    public function grantComplimentaryForLicense(License $license): ?StudioCreditTransaction
    {
        $credits = max(0, (int) config('design-studio.complimentary_credits_per_asset_license', 1));
        if ($credits === 0 || ! $license->asset_id) {
            return null;
        }

        return StudioCreditTransaction::firstOrCreate(
            [
                'license_id' => $license->id,
                'type' => StudioCreditTransaction::TYPE_COMPLIMENTARY,
            ],
            [
                'user_id' => $license->user_id,
                'status' => StudioCreditTransaction::STATUS_POSTED,
                'credits' => $credits,
                'currency' => 'USD',
                'metadata' => [
                    'reason' => 'Complimentary Creative Studio export credit included with an asset license.',
                    'asset_id' => $license->asset_id,
                ],
                'posted_at' => now(),
            ],
        );
    }

    public function reserveForExport(User $user, DesignExport $export): StudioCreditTransaction
    {
        return DB::transaction(function () use ($user, $export): StudioCreditTransaction {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $existing = StudioCreditTransaction::query()
                ->where('design_export_id', $export->id)
                ->where('type', StudioCreditTransaction::TYPE_EXPORT)
                ->first();

            if ($existing) {
                return $existing;
            }

            if ($this->availableBalance($user->id) < 1) {
                throw ValidationException::withMessages([
                    'studio_credits' => 'A Creative Studio export credit is required. Purchase credits to create a new finished export.',
                ]);
            }

            $transaction = StudioCreditTransaction::create([
                'user_id' => $user->id,
                'design_export_id' => $export->id,
                'type' => StudioCreditTransaction::TYPE_EXPORT,
                'status' => StudioCreditTransaction::STATUS_PENDING,
                'credits' => -1,
                'currency' => strtoupper((string) config('design-studio.currency', 'USD')),
                'metadata' => ['design_project_id' => $export->design_project_id],
            ]);

            $export->forceFill([
                'studio_credit_transaction_id' => $transaction->id,
                'studio_price_cents' => (int) config('design-studio.single_export_reference_price_cents', 100),
                'studio_billing_type' => 'credit',
                'studio_billing_snapshot' => [
                    'credits_consumed' => 1,
                    'reference_price_cents' => (int) config('design-studio.single_export_reference_price_cents', 100),
                    'currency' => strtoupper((string) config('design-studio.currency', 'USD')),
                    'policy' => 'one_credit_per_successful_export',
                ],
            ])->save();

            return $transaction;
        }, attempts: 3);
    }

    public function consumeForExport(DesignExport $export): void
    {
        DB::transaction(function () use ($export): void {
            $transaction = StudioCreditTransaction::query()
                ->where('design_export_id', $export->id)
                ->where('type', StudioCreditTransaction::TYPE_EXPORT)
                ->lockForUpdate()
                ->first();

            if (! $transaction || $transaction->status === StudioCreditTransaction::STATUS_POSTED) {
                return;
            }

            if ($transaction->status === StudioCreditTransaction::STATUS_VOID) {
                throw new \RuntimeException('The Studio credit reservation for this export has already been released.');
            }

            $transaction->forceFill([
                'status' => StudioCreditTransaction::STATUS_POSTED,
                'posted_at' => now(),
                'voided_at' => null,
            ])->save();
        }, attempts: 3);
    }

    public function releaseForExport(DesignExport $export): void
    {
        StudioCreditTransaction::query()
            ->where('design_export_id', $export->id)
            ->where('type', StudioCreditTransaction::TYPE_EXPORT)
            ->where('status', StudioCreditTransaction::STATUS_PENDING)
            ->update([
                'status' => StudioCreditTransaction::STATUS_VOID,
                'voided_at' => now(),
                'updated_at' => now(),
            ]);
    }
}

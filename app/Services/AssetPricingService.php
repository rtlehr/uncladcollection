<?php

namespace App\Services;

use App\Enums\AssetPricingTierType;
use App\Models\Asset;
use App\Models\AssetOffering;
use App\Models\AssetPricingTier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetPricingService
{
    public function saveMany(Asset $asset, array $tiers): void
    {
        $this->validateTierRanges($tiers);

        DB::transaction(function () use ($asset, $tiers): void {
            $asset->pricingTiers()->delete();

            foreach (array_values($tiers) as $index => $data) {
                $type = AssetPricingTierType::from($data['pricing_type']);

                $asset->pricingTiers()->create([
                    'asset_offering_id' => Arr::get($data, 'asset_offering_id'),
                    'minimum_quantity' => (int) $data['minimum_quantity'],
                    'maximum_quantity' => Arr::get($data, 'maximum_quantity'),
                    'pricing_type' => $type,
                    'unit_price_cents' => $type === AssetPricingTierType::FixedUnitPrice
                        ? (int) ($data['unit_price_cents'] ?? 0)
                        : null,
                    'percentage_off' => $type === AssetPricingTierType::PercentageOff
                        ? (float) ($data['percentage_off'] ?? 0)
                        : null,
                    'currency' => strtoupper($data['currency'] ?? 'USD'),
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => (bool) ($data['is_active'] ?? true),
                ]);
            }
        });
    }

    public function activeForOffering(Asset $asset, ?AssetOffering $offering): \Illuminate\Support\Collection
    {
        return $asset->pricingTiers()
            ->where('is_active', true)
            ->where(function ($query) use ($offering): void {
                $query->whereNull('asset_offering_id');
                if ($offering) {
                    $query->orWhere('asset_offering_id', $offering->id);
                }
            })
            ->orderByRaw('asset_offering_id is null')
            ->orderBy('minimum_quantity')
            ->orderBy('sort_order')
            ->get();
    }

    private function validateTierRanges(array $tiers): void
    {
        $grouped = collect($tiers)->groupBy(fn (array $tier) => (string) ($tier['asset_offering_id'] ?? 'asset'));

        foreach ($grouped as $group) {
            $sorted = $group->sortBy(fn (array $tier) => (int) $tier['minimum_quantity'])->values();
            $previousMaximum = 0;

            foreach ($sorted as $tier) {
                $minimum = (int) $tier['minimum_quantity'];
                $maximum = isset($tier['maximum_quantity']) && $tier['maximum_quantity'] !== ''
                    ? (int) $tier['maximum_quantity']
                    : null;

                if ($minimum < 1 || ($maximum !== null && $maximum < $minimum) || $minimum <= $previousMaximum) {
                    throw ValidationException::withMessages([
                        'pricing_tiers' => 'Pricing tiers for the same offering must use valid, non-overlapping quantity ranges.',
                    ]);
                }

                $previousMaximum = $maximum ?? PHP_INT_MAX;
            }
        }
    }
}

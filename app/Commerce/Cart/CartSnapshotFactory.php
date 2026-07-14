<?php

namespace App\Commerce\Cart;

use App\Commerce\Configuration\ConfigurationSelection;
use App\Commerce\Pricing\PriceBreakdown;
use App\Models\AssetOffering;

class CartSnapshotFactory
{
    public function configuration(ConfigurationSelection $selection): array
    {
        return $selection->toSnapshotArray();
    }

    public function pricing(PriceBreakdown $price): array
    {
        return $price->toArray();
    }

    public function offering(AssetOffering $offering): array
    {
        return [
            'version' => 1,
            'asset_id' => $offering->asset_id,
            'asset_title' => $offering->asset?->title,
            'offering_id' => $offering->id,
            'offering_name' => $offering->name,
            'license_type_id' => $offering->license_type_id,
            'license_name' => $offering->licenseType?->name,
            'currency' => strtoupper($offering->currency),
        ];
    }

    public function shippingAddress(?array $address): ?array
    {
        if (! $address) {
            return null;
        }

        return [
            ...$address,
            'snapshot_version' => 1,
        ];
    }
}

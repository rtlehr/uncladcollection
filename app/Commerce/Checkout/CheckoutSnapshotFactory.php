<?php

namespace App\Commerce\Checkout;

use App\Models\AssetFile;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;

final class CheckoutSnapshotFactory
{
    public const VERSION = 1;

    /** @param iterable<int, CartItem> $cartItems */
    public function order(iterable $cartItems): array
    {
        $items = [];

        foreach ($cartItems as $cartItem) {
            $items[] = [
                'cart_item_id' => $cartItem->id,
                'kind' => $cartItem->asset_id ? 'asset' : 'legacy_image',
                'asset_id' => $cartItem->asset_id,
                'asset_offering_id' => $cartItem->asset_offering_id,
                'image_id' => $cartItem->image_id,
                'license_type_id' => $cartItem->license_type_id,
                'quantity' => (int) $cartItem->quantity,
                'configuration_hash' => $cartItem->configuration_hash,
                'configuration' => $cartItem->configuration_snapshot,
                'shipping_address' => $cartItem->shipping_address_snapshot,
                'pricing' => $cartItem->pricing_snapshot,
                'line_total_cents' => (int) ($cartItem->line_total_cents ?? $cartItem->price_cents),
                'currency' => strtoupper($cartItem->currency),
            ];
        }

        return [
            'version' => self::VERSION,
            'captured_at' => now()->toIso8601String(),
            'items' => $items,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function includedFiles(CartItem $cartItem): array
    {
        if (! $cartItem->assetOffering) {
            return [];
        }

        return $cartItem->assetOffering
            ->includedFiles()
            ->map(fn (AssetFile $file): array => [
                'asset_file_id' => $file->id,
                'uuid' => $file->uuid,
                'role' => $file->role?->value ?? (string) $file->role,
                'media_type' => $file->media_type?->value ?? (string) $file->media_type,
                'original_filename' => $file->original_filename,
                'extension' => $file->extension,
                'mime_type' => $file->mime_type,
                'size_bytes' => $file->size_bytes,
                'checksum_sha256' => $file->checksum_sha256,
            ])
            ->values()
            ->all();
    }

    public function orderItem(OrderItem $item): array
    {
        return [
            'version' => self::VERSION,
            'order_item_id' => $item->id,
            'asset_id' => $item->asset_id,
            'asset_offering_id' => $item->asset_offering_id,
            'image_id' => $item->image_id,
            'quantity' => $item->quantity,
            'configuration_hash' => $item->configuration_hash,
            'configuration' => $item->configuration_snapshot,
            'shipping_address' => $item->shipping_address_snapshot,
            'pricing' => $item->pricing_snapshot,
            'included_files' => $item->included_asset_files_snapshot,
        ];
    }
}

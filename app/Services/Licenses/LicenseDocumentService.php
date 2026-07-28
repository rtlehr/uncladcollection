<?php

namespace App\Services\Licenses;

use App\Models\License;

class LicenseDocumentService
{
    public function __construct(private readonly SimplePdfDocument $pdf) {}

    public function certificate(License $license): string
    {
        $license->loadMissing(['user', 'asset', 'image', 'order', 'orderItem']);
        $product = $license->asset?->title ?? $license->image?->title ?? 'Purchased asset';
        $customer = $license->user?->name ?: $license->user?->email ?: 'Customer';
        $files = collect($license->included_asset_files_snapshot ?? [])->pluck('original_filename')->filter()->values();

        $lines = [
            'UNCLAD COLLECTION',
            'LICENSE CERTIFICATE',
            '',
            'Certificate generated: '.now()->format('F j, Y g:i A T'),
            'License key: '.$license->license_key,
            'Customer: '.$customer,
            'Customer email: '.($license->user?->email ?? 'Not available'),
            'Asset: '.$product,
            'License: '.$license->license_name,
            'Order number: '.($license->order?->order_number ?? 'Not available'),
            'Purchase date: '.($license->order?->paid_at?->format('F j, Y') ?? $license->created_at?->format('F j, Y') ?? 'Not available'),
            'Effective date: '.($license->starts_at?->format('F j, Y') ?? 'At purchase'),
            'Expiration date: '.($license->expires_at?->format('F j, Y') ?? 'No expiration'),
            'Status at generation: '.ucfirst($license->status),
            '',
            'PURCHASED LICENSE TERMS',
            $license->license_terms ?: 'No written terms were stored with this historical license.',
        ];

        if ($files->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'INCLUDED FILES';
            foreach ($files as $file) {
                $lines[] = '- '.$file;
            }
        }

        $lines[] = '';
        $lines[] = 'This certificate is generated from the immutable purchase record retained by Unclad Collection. It does not expand or replace the purchased license terms.';

        return $this->pdf->render($lines, 'License Certificate '.$license->license_key);
    }

    public function proofOfPurchase(License $license): string
    {
        $license->loadMissing(['user', 'asset', 'image', 'order', 'orderItem']);
        $product = $license->asset?->title ?? $license->image?->title ?? 'Purchased asset';
        $order = $license->order;

        $lines = [
            'UNCLAD COLLECTION',
            'PROOF OF PURCHASE',
            '',
            'Document generated: '.now()->format('F j, Y g:i A T'),
            'Order number: '.($order?->order_number ?? 'Not available'),
            'Customer: '.($license->user?->name ?? 'Customer'),
            'Customer email: '.($license->user?->email ?? 'Not available'),
            'Purchase date: '.($order?->paid_at?->format('F j, Y') ?? $license->created_at?->format('F j, Y') ?? 'Not available'),
            'Order status: '.ucfirst((string) ($order?->status ?? 'unknown')),
            'Asset: '.$product,
            'License: '.$license->license_name,
            'License key: '.$license->license_key,
            'Quantity: '.max(1, (int) ($license->orderItem?->quantity ?? 1)),
            'Line total: '.($license->orderItem?->total_price_formatted ?? 'Not available'),
            'Order total: '.($order?->total_formatted ?? 'Not available'),
            'Currency: '.strtoupper((string) ($order?->currency ?? 'USD')),
            '',
            'This document confirms the purchase record maintained by Unclad Collection. It is not a payment-card statement and does not disclose protected payment credentials.',
        ];

        return $this->pdf->render($lines, 'Proof of Purchase '.$license->license_key);
    }
}

import type { AssetOption } from '@/types/asset';
import type { PaginationLink } from '@/types/common';

export interface PurchaseOrderSummary {
    id: number | null;
    order_number: string | null;
    paid_at: string | null;
    total_formatted: string | null;
    line_total_formatted: string | null;
}

export interface PurchaseConfigurationLabel {
    group: string;
    values: string[];
}

export interface PurchaseConfigurationSnapshot {
    labels?: PurchaseConfigurationLabel[];
    selections?: Record<string, unknown>;
    [key: string]: unknown;
}

export interface PurchasedProduct {
    id: number;
    title: string;
    slug: string;
    creator: string | null;
    preview_url: string | null;
    is_ai_generated: boolean;
    asset_type_label: string;
    public_url: string;
}

export interface PurchasedAsset {
    id: number;
    kind: 'asset' | 'legacy_image';
    license_key: string;
    license_name: string;
    downloads_used: number;
    download_limit: number | null;
    starts_at: string | null;
    expires_at: string | null;
    can_download: boolean;
    detail_url: string;
    download_url: string | null;
    quantity: number;
    configuration: PurchaseConfigurationSnapshot | null;
    included_files_count: number;
    product: PurchasedProduct;
    order: PurchaseOrderSummary;
}

export interface PaginatedPurchases {
    data: PurchasedAsset[];
    links: PaginationLink[];
    meta?: unknown;
}

export interface PurchaseDetailProduct extends PurchasedProduct {
    description: string | null;
    created_at: string | null;
    collection: AssetOption | null;
    categories: AssetOption[];
    tags: AssetOption[];
}

export interface PurchasedIncludedFile {
    id: number | null;
    name: string;
    role: string | null;
    media_type: string | null;
    extension: string | null;
    mime_type: string | null;
    size_bytes: number | null;
}

export interface PurchaseDetailRecord {
    id: number;
    kind: 'asset' | 'legacy_image';
    license_key: string;
    license_name: string;
    license_terms: string | null;
    downloads_used: number;
    download_limit: number | null;
    starts_at: string | null;
    expires_at: string | null;
    can_download: boolean;
    download_url: string | null;
    quantity: number;
    configuration: PurchaseConfigurationSnapshot | null;
    pricing: Record<string, unknown> | null;
    included_files: PurchasedIncludedFile[];
    product: PurchaseDetailProduct;
    order: PurchaseOrderSummary;
}

import type { AssetOption } from '@/types/asset';
import type { PaginationLink } from '@/types/common';

export interface PurchaseOrderSummary {
    id: number | null;
    order_number: string | null;
    paid_at: string | null;
    total_formatted: string | null;
}

export interface PurchasedAssetImage {
    id: number;
    title: string;
    slug: string;
    photographer: string | null;
    thumbnail_url: string | null;
    icon_url: string | null;
    is_ai_generated: boolean;
    favorites_count: number;
    downloads_count: number;
    purchases_count: number;
    views_count: number;
}

export interface PurchasedAsset {
    id: number;
    license_key: string;
    license_name: string;
    downloads_used: number;
    download_limit: number | null;
    starts_at: string | null;
    expires_at: string | null;
    image: PurchasedAssetImage;
    order: PurchaseOrderSummary;
}

export interface PaginatedPurchases {
    data: PurchasedAsset[];
    links: PaginationLink[];
    meta?: unknown;
}

export interface PurchaseDetailImage {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    photographer: string | null;
    thumbnail_url: string | null;
    high_res_url: string | null;
    original_url: string | null;
    is_ai_generated: boolean;
    created_at: string | null;
    collection: AssetOption | null;
    categories: AssetOption[];
    tags: AssetOption[];
}

export interface PurchaseDetailRecord {
    id: number;
    license_key: string;
    license_name: string;
    license_terms: string | null;
    downloads_used: number;
    download_limit: number | null;
    starts_at: string | null;
    expires_at: string | null;
    can_download: boolean;
    image: PurchaseDetailImage;
    order: PurchaseOrderSummary;
}

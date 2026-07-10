import type { NamedItem, PaginationLink } from '@/types/common';

export type AssetType = 'raster' | 'vector' | 'archive';

export interface AssetOption extends NamedItem {}

export interface AssetCardData {
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

    collection: AssetOption | null;
    categories: AssetOption[];
    tags: AssetOption[];

    asset_type?: AssetType;
    file_extension?: string | null;
    mime_type?: string | null;
}

export interface AssetDetailData extends AssetCardData {
    description: string | null;

    original_url: string | null;
    high_res_url: string | null;

    is_favorited: boolean;
    is_purchased: boolean;
    can_purchase: boolean;
    can_download: boolean;

    created_at: string | null;
}

export interface RelatedAssetData {
    id: number;
    title: string;
    slug: string;

    thumbnail_url: string | null;
    icon_url?: string | null;

    is_ai_generated: boolean;

    favorites_count: number;
    views_count: number;

    asset_type?: AssetType;
    file_extension?: string | null;
    mime_type?: string | null;
}

export interface FavoriteAssetData {
    id: number;
    title: string;
    slug: string;

    thumbnail_url: string | null;
    icon_url?: string | null;

    is_ai_generated: boolean;

    favorites_count: number;
    views_count: number;

    collection: AssetOption | null;
    categories: AssetOption[];

    asset_type?: AssetType;
    file_extension?: string | null;
    mime_type?: string | null;
}

export interface LicenseType {
    id: number;
    name: string;
    description: string | null;
    price_cents: number;
    currency: string;
}

export interface PaginatedAssets {
    data: AssetCardData[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

export interface PaginatedFavoriteAssets {
    data: FavoriteAssetData[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

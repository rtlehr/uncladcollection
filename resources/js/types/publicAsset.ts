import type { MediaPresentationFile } from '@/types/mediaPresentation';

export interface PublicAssetFile extends MediaPresentationFile {}

export interface PublicAssetOffering {
    id: number;
    name: string;
    description: string | null;
    price_cents: number;
    currency: string;
    download_limit: number | null;
    expires_after_days: number | null;
    include_all_active_files: boolean;
    license_type: { id: number; name: string; slug: string; description: string | null };
    files: PublicAssetFile[];
    total_size_bytes: number;
}

export interface PublicAsset {
    id: number;
    uuid: string;
    title: string;
    slug: string;
    description: string | null;
    asset_type: string;
    asset_type_label: string;
    photographer: string | null;
    is_ai_generated: boolean;
    views_count: number;
    downloads_count: number;
    favorites_count: number;
    published_at: string | null;
    collection: { id: number; name: string; slug: string } | null;
    preview: PublicAssetFile | null;
    poster: PublicAssetFile | null;
    files: PublicAssetFile[];
    formats: string[];
    legacy_image_url: string | null;
}

export interface RelatedPublicAsset {
    id: number;
    title: string;
    slug: string;
    asset_type: string;
    asset_type_label: string;
    preview_url: string | null;
    formats: string[];
}

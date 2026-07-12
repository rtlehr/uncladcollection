export interface SelectOption {
    value: string;
    label: string;
}

export interface NamedOption {
    id: number;
    name: string;
}

export interface AdminAssetFile {
    id: number;
    uuid: string;
    role: string;
    media_type: string;
    original_filename: string;
    extension: string;
    mime_type: string | null;
    size_bytes: number | null;
    sort_order: number;
    width: number | null;
    height: number | null;
    duration_seconds: string | null;
    processing_status: string;
    virus_scan_status: string;
    is_downloadable: boolean;
    is_active: boolean;
    is_legacy: boolean;
    is_primary_preview: boolean;
    is_poster: boolean;
    public_url: string | null;
}

export interface AdminAsset {
    id: number;
    uuid: string;
    title: string;
    slug: string;
    description: string | null;
    collection_id: number | null;
    collection: NamedOption | null;
    asset_type: string;
    status: string;
    photographer: string | null;
    sort_order: number;
    is_active: boolean;
    is_featured: boolean;
    is_ai_generated: boolean;
    files_count: number;
    active_files_count: number;
    primary_preview_file_id: number | null;
    poster_file_id: number | null;
    preview_url: string | null;
    legacy_image_id: number | null;
    files?: AdminAssetFile[];
    offerings?: AdminAssetOffering[];
}

export interface PendingAssetFile {
    id: string;
    file: File;
    role: string;
    downloadable: boolean;
    previewUrl: string | null;
}

export interface AdminAssetOffering {
    id: number | null;
    license_type_id: number;
    name: string;
    description: string | null;
    price_cents: number;
    currency: string;
    download_limit: number | null;
    expires_after_days: number | null;
    include_all_active_files: boolean;
    is_active: boolean;
    file_ids: number[];
}

export interface LicenseTypeOption {
    id: number;
    name: string;
    description: string | null;
    price_cents: number;
    currency: string;
    download_limit: number | null;
    expires_after_days: number | null;
}

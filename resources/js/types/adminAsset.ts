import type { MediaPreviewKind } from '@/types/mediaPresentation';

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
    can_preview: boolean;
    preview_kind: MediaPreviewKind;
    preview_url: string | null;
    poster_url: string | null;
    preview_note: string | null;
}


export interface AssetHealthCheck {
    key: string;
    label: string;
    complete: boolean;
}

export interface AssetHealth {
    score: number;
    status: 'ready' | 'needs_review' | 'needs_attention';
    checks: AssetHealthCheck[];
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
    allows_quantity: boolean;
    fulfillment_type: 'digital' | 'physical' | 'hybrid';
    collects_shipping_address: boolean;
    shipping_address_required: boolean;
    files_count: number;
    active_files_count: number;
    primary_preview_file_id: number | null;
    poster_file_id: number | null;
    preview_url: string | null;
    marketplace_image_url: string | null;
    marketplace_image_edit_data: Record<string, unknown> | null;
    marketplace_source_asset_file_id: number | null;
    legacy_image_id: number | null;
    health: AssetHealth;
    files?: AdminAssetFile[];
    offerings?: AdminAssetOffering[];
    configurations?: AdminAssetConfigurationGroup[];
}

export interface PendingAssetFileMetadata {
    extension: string;
    mimeType: string;
    sizeBytes: number;
    width: number | null;
    height: number | null;
    durationSeconds: number | null;
    kind: 'image' | 'video' | 'archive' | 'document' | 'vector' | 'other';
}

export interface PendingAssetFile {
    id: string;
    file: File;
    role: string;
    downloadable: boolean;
    previewUrl: string | null;
    metadata: PendingAssetFileMetadata;
    validationErrors: string[];
}

export interface AdminAssetPricingTier { id: number | null; minimum_quantity: number; maximum_quantity: number | null; pricing_type: 'fixed_unit_price' | 'percentage_off'; unit_price_cents: number | null; percentage_off: number | null; currency: string; is_active: boolean; }

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
    pricing_tiers: AdminAssetPricingTier[];
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


export interface ConfigurationDisplayTypeOption { value: string; label: string; uses_values: boolean; }
export interface AdminAssetConfigurationValue { id: number | null; label: string; value: string; description: string | null; swatch_color: string | null; image_path: string | null; is_default: boolean; is_active: boolean; price_adjustment_cents: number; currency: string; }
export interface AdminAssetConfigurationGroup { id: number | null; name: string; code: string; display_type: string; is_required: boolean; allows_multiple: boolean; placeholder: string | null; help_text: string | null; minimum_value: string | number | null; maximum_value: string | number | null; step_value: string | number | null; is_active: boolean; values: AdminAssetConfigurationValue[]; }

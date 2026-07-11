export interface AdminLicenseType {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price_cents: number;
    currency: string;
    download_limit: number | null;
    expires_after_days: number | null;
    max_resolution: string;
    usage_terms?: string | null;
    is_active: boolean;
    sort_order: number;
}

export interface EditableAdminLicenseType {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    currency: string;
    download_limit: number | null;
    expires_after_days: number | null;
    max_resolution: string;
    usage_terms: string | null;
    is_active: boolean;
    sort_order: number;
}

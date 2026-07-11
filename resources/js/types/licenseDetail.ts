export interface AdminLicenseDownloadRecord {
    id: number;
    download_type: string;
    ip_address: string | null;
    user_agent: string | null;
    downloaded_at: string | null;
}

export interface AdminLicenseUser {
    id: number;
    name: string;
    email: string;
}

export interface AdminLicenseImage {
    id: number;
    title: string;
    slug: string;
    photographer: string | null;
    is_ai_generated: boolean;
}

export interface AdminLicenseOrder {
    id: number;
    order_number: string;
    status: string;
    total_formatted: string;
    paid_at: string | null;
}

export interface AdminLicenseOrderItem {
    id: number;
    status: string;
    unit_price_formatted: string;
    total_price_formatted: string;
}

export interface AdminLicenseDetail {
    id: number;
    license_key: string;
    status: string;
    license_name: string;
    license_terms: string | null;
    downloads_used: number;
    download_limit: number | null;
    starts_at: string | null;
    expires_at: string | null;
    created_at: string | null;
    user: AdminLicenseUser | null;
    image: AdminLicenseImage | null;
    order: AdminLicenseOrder | null;
    order_item: AdminLicenseOrderItem | null;
    downloads: AdminLicenseDownloadRecord[];
}

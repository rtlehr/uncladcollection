export interface AdminDownloadUser {
    id: number;
    name: string;
    email: string;
}

export interface AdminDownloadImage {
    id: number;
    title: string;
    slug: string;
    photographer: string | null;
    icon_url: string | null;
}

export interface AdminDownloadLicense {
    id: number;
    license_key: string;
    license_name: string;
    status: string;
    downloads_used: number;
    download_limit: number | null;
    order_id: number | null;
}

export interface AdminDownloadOrder {
    id: number;
    order_number: string;
    status: string;
    total_formatted: string;
    paid_at: string | null;
}

export interface AdminDownloadDetail {
    id: number;
    download_type: string;
    ip_address: string | null;
    user_agent: string | null;
    downloaded_at: string | null;
    created_at: string | null;
    user: AdminDownloadUser | null;
    image: AdminDownloadImage | null;
    license: AdminDownloadLicense | null;
    order: AdminDownloadOrder | null;
}

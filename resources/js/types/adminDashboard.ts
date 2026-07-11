export interface AdminDashboardStats {
    total_revenue_formatted: string;
    total_orders: number;
    paid_orders: number;
    active_licenses: number;
    total_downloads: number;
    total_images: number;
    active_images: number;
    total_users: number;
}

export interface AdminDashboardOrder {
    id: number;
    order_number: string;
    status: string;
    total_formatted: string;
    created_at: string | null;
    user: {
        name: string;
        email: string;
    } | null;
}

export interface AdminDashboardDownload {
    id: number;
    download_type: string;
    downloaded_at: string | null;
    user: {
        name: string;
        email: string;
    } | null;
    image: {
        title: string;
        slug: string;
    } | null;
    license: {
        id: number;
        license_name: string;
    } | null;
}

export interface AdminDashboardImage {
    id: number;
    title: string;
    slug: string;
    purchases_count: number;
    downloads_count: number;
}

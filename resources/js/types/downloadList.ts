import type { PaginationLink } from '@/types/common';

export interface AdminDownloadListUser {
    id: number;
    name: string;
    email: string;
}

export interface AdminDownloadListImage {
    id: number;
    title: string;
    slug: string;
}

export interface AdminDownloadListLicense {
    id: number;
    license_key: string;
    license_name: string;
}

export interface AdminDownloadListOrder {
    id: number;
    order_number: string;
}

export interface AdminDownloadListItem {
    id: number;
    download_type: string;
    ip_address: string | null;
    downloaded_at: string | null;
    user: AdminDownloadListUser | null;
    image: AdminDownloadListImage | null;
    license: AdminDownloadListLicense | null;
    order: AdminDownloadListOrder | null;
}

export interface AdminDownloadFilters {
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
}

export interface PaginatedAdminDownloads {
    data: AdminDownloadListItem[];
    links: PaginationLink[];
    meta?: unknown;
    from?: number | null;
    to?: number | null;
    total?: number | null;
}

import type { PaginationLink } from '@/types/common';

export interface AdminLicenseListUser {
    id: number;
    name: string;
    email: string;
}

export interface AdminLicenseListImage {
    id: number;
    title: string;
    slug: string;
}

export interface AdminLicenseListOrder {
    id: number;
    order_number: string;
}

export interface AdminLicenseListItem {
    id: number;
    license_key: string;
    status: string;
    license_name: string;
    downloads_used: number;
    download_limit: number | null;
    downloads_count: number;
    starts_at: string | null;
    expires_at: string | null;
    created_at: string | null;
    user: AdminLicenseListUser | null;
    image: AdminLicenseListImage | null;
    order: AdminLicenseListOrder | null;
}

export interface AdminLicenseFilters {
    search: string;
    status: string;
    sort: string;
    direction: 'asc' | 'desc';
}

export interface PaginatedAdminLicenses {
    data: AdminLicenseListItem[];
    links: PaginationLink[];
    meta?: unknown;
    from?: number | null;
    to?: number | null;
    total?: number | null;
}

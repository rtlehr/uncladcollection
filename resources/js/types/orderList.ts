import type { PaginationLink } from '@/types/common';

export interface AdminOrderListUser {
    id: number;
    name: string;
    email: string;
}

export interface AdminOrderListItem {
    id: number;
    order_number: string;
    status: string;
    total_formatted: string;
    currency: string;
    payment_provider: string | null;
    paid_at: string | null;
    created_at: string | null;
    items_count: number;
    licenses_count: number;
    user: AdminOrderListUser | null;
}

export interface AdminOrderFilters {
    search: string;
    status: string;
    sort: string;
    direction: 'asc' | 'desc';
}

export interface PaginatedAdminOrders {
    data: AdminOrderListItem[];
    links: PaginationLink[];
    meta?: unknown;
    from?: number | null;
    to?: number | null;
    total?: number | null;
}

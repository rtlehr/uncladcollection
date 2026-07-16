import type { PaginationLink } from '@/types/common';

export interface AdminBlogPerson {
    id: number;
    name: string;
    email?: string;
}

export interface AdminBlogOption {
    id: number;
    name: string;
}

export interface AdminBlogActivity {
    id: number;
    action?: string;
    description: string;
    created_at: string;
    user?: {
        id: number;
        name: string;
    } | null;
}

export interface AdminBlogListItem {
    id: number;
    title: string;
    slug: string;
    status: string;
    is_featured: boolean;
    is_active: boolean;
    published_at: string | null;
    expires_at: string | null;
    views_count: number;
    created_at?: string | null;
    author?: AdminBlogPerson;
    categories?: AdminBlogOption[];
    tags?: AdminBlogOption[];
}

export interface AdminBlogFilters {
    search: string;
    status: string;
    sort: string;
    direction: 'asc' | 'desc';
}

export interface PaginatedAdminBlogPosts {
    data: AdminBlogListItem[];
    links: PaginationLink[];
    meta?: unknown;
    from?: number | null;
    to?: number | null;
    total?: number | null;
}

export interface AdminBlogPostDetail {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string | null;

    featured_image_url: string | null;
    header_image_url: string | null;
    header_image_original_url: string | null;
    icon_image_url: string | null;
    icon_image_original_url: string | null;
    image_edit_data: {
        header?: Record<string, unknown>;
        icon?: Record<string, unknown>;
    } | null;

    status: string;
    published_at: string | null;
    expires_at?: string | null;
    created_at: string;
    updated_at: string;

    seo_title: string | null;
    seo_description: string | null;

    is_featured: boolean;
    is_active: boolean;
    views_count: number;

    comments_enabled?: boolean;
    comments_visible?: boolean;
    comments_require_approval?: boolean;

    category_ids?: number[];
    tag_ids?: number[];

    author: AdminBlogPerson | null;
    categories: AdminBlogOption[];
    tags: AdminBlogOption[];
}

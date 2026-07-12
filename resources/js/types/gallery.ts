export interface GalleryOption {
    id: number;
    name: string;
}

export interface GalleryImage {
    id: number;
    title: string;
    slug: string;
    photographer: string | null;
    thumbnail_url: string | null;
    icon_url: string | null;
    is_ai_generated: boolean;
    is_favorited: boolean;
    favorites_count: number;
    downloads_count: number;
    purchases_count: number;
    views_count: number;
    collection: {
        id: number;
        name: string;
        slug?: string | null;
    } | null;
    categories: GalleryOption[];
    tags: GalleryOption[];
}

export interface GalleryPaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedGalleryImages {
    data: GalleryImage[];
    links: GalleryPaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

export interface GalleryFilters {
    search: string;
    category_id: string;
    tag_id: string;
    collection_id: string;
    ai_generated: string;
    sort: string;
}

export interface GalleryActiveLicense {
    id: number;
    license_name: string;
    status: string;
    downloads_used: number;
    download_limit: number | null;
    expires_at: string | null;
}

export interface GalleryImageDetail extends GalleryImage {
    description: string | null;
    original_url: string | null;
    high_res_url: string | null;
    created_at: string | null;
    is_purchased: boolean;
    can_purchase: boolean;
    can_download: boolean;
    active_license: GalleryActiveLicense | null;
}

export interface GalleryLicenseType {
    id: number;
    name: string;
    description: string | null;
    price_cents: number;
    currency: string;
    download_limit: number | null;
    expires_after_days: number | null;
    max_resolution: string | null;
}

export interface GalleryNavigationImage {
    id: number;
    title: string;
    slug: string;
}

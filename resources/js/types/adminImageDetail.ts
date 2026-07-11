export interface AdminImageCollection {
    id: number;
    name: string;
}

export interface AdminImageOption {
    id: number;
    name: string;
}

export interface AdminImageDetail {
    id: number;
    title: string;
    slug: string;
    description: string | null;

    original_url: string | null;
    high_res_url: string | null;
    thumbnail_url: string | null;
    icon_url: string | null;

    photographer: string | null;
    sort_order: number;
    is_active: boolean;
    is_ai_generated: boolean;

    downloads_count: number;
    favorites_count: number;
    purchases_count: number;
    views_count: number;

    collection: AdminImageCollection | null;
    categories: AdminImageOption[];
    tags: AdminImageOption[];

    created_at: string | null;
    updated_at: string | null;
}

export interface AdminImageActivity {
    id: number;
    admin_name: string;
    action: string;
    field_name: string | null;
    old_value: string | null;
    new_value: string | null;
    description: string | null;
    created_at: string | null;
}

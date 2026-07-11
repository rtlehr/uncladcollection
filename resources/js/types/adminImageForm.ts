export interface AdminImageOption {
    id: number;
    name: string;
}

export interface AdminEditableImage {
    id: number;
    collection_id: number | null;
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
    categories: number[];
    tags: number[];
}

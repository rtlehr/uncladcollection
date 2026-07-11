export interface AdminImageCollection {
    id: number;
    name: string;
}

export interface AdminImageListItem {
    id: number;
    title: string;
    slug: string;
    thumbnail_url: string | null;
    photographer: string | null;
    sort_order: number;
    is_active: boolean;
    collection: AdminImageCollection | null;
}

export interface AdminImageListFilters {
    search: string;
    status: string;
    collection_id: string;
    sort: string;
    direction: 'asc' | 'desc';
}

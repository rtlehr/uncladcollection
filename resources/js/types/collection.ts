export interface AdminCollection {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
    is_active: boolean;
    created_at?: string;
}

export interface AdminCollectionFilters {
    search: string;
    status: string;
    sort: string;
    direction: 'asc' | 'desc';
}

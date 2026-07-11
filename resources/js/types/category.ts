export interface AdminCategory {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    category_type: string;
    sort_order: number;
    is_active: boolean;
    created_at?: string;
}

export interface AdminCategoryFilters {
    search: string;
    type: string;
    sort: string;
    direction: 'asc' | 'desc';
}

export type CategoryTypes = Record<string, string>;

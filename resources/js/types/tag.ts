export interface AdminTag {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    tag_type: string;
    created_at?: string;
}

export interface AdminTagFilters {
    search: string;
    type: string;
    sort: string;
    direction: 'asc' | 'desc';
}

export type TagTypes = Record<string, string>;

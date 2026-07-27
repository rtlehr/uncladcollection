export interface PublicSearchSuggestion {
    type: 'asset' | 'category' | 'tag' | 'collection' | 'creator' | 'photographer' | 'author' | 'term' | 'recent' | 'popular';
    label: string;
    value: string;
    href?: string | null;
    meta?: string | null;
}

export interface PublicActiveFilter {
    key: string;
    label: string;
}

export interface PublicResultSummary {
    from: number | null;
    to: number | null;
    total: number;
    itemLabel: string;
}

export interface AdminUserListItem {
    id: number;
    name: string;
    username: string | null;
    email: string;
    is_disabled: boolean;
    roles: string[];
    direct_permissions_count: number;
    all_permissions_count: number;
    created_at: string;
}

export interface AdminUserFilters {
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
}

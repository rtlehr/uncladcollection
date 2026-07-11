export interface AdminUserRole {
    id: number;
    name: string;
    label: string;
}

export interface AdminUserPermission {
    id: number;
    name: string;
    label: string;
    group_name: string | null;
}

export interface AdminUserRecord {
    id: number;
    name: string;
    username: string | null;
    email: string;
    is_disabled: boolean;
    roles: AdminUserRole[];
    permissions: AdminUserPermission[];
    all_permissions_count: number;
    created_at: string | null;
    updated_at: string | null;
}

export interface AdminActivityRecord {
    id: number;
    admin_name: string;
    action: string;
    field_name: string | null;
    old_value: string | null;
    new_value: string | null;
    description: string | null;
    created_at: string | null;
}

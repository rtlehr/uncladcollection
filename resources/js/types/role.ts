export interface Permission {
    id: number;
    name: string;
    label: string;
    group_name: string | null;
    description: string | null;
}

export interface AdminRoleListItem {
    id: number;
    name: string;
    label: string;
    description: string | null;
    is_system: boolean;
    is_locked: boolean;
    permissions_count: number;
    users_count: number;
}

export interface AdminRoleDetail {
    id: number;
    name: string;
    label: string;
    description: string | null;
    is_system: boolean;
    is_locked: boolean;
    permissions: Permission[];
}

export type GroupedPermissions = Record<string, Permission[]>;

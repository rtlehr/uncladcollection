export interface AdminPermission {
    id: number;
    name: string;
    label: string;
    group_name: string | null;
    description: string | null;
    is_system: boolean;
    is_locked: boolean;
}

export type GroupedAdminPermissions = Record<string, AdminPermission[]>;

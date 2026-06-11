<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

import ActivityLog from '@/components/admin/ActivityLog.vue';

type Role = {
    id: number;
    name: string;
    label: string;
};

type Permission = {
    id: number;
    name: string;
    label: string;
    group_name: string | null;
};

type UserRecord = {
    id: number;
    name: string;
    username: string | null;
    email: string;
    is_disabled: boolean;
    roles: Role[];
    permissions: Permission[];
    all_permissions_count: number;
    created_at: string | null;
    updated_at: string | null;
};

type Activity = {
    id: number;
    admin_name: string;
    action: string;
    field_name: string | null;
    old_value: string | null;
    new_value: string | null;
    description: string | null;
    created_at: string | null;
};

defineProps<{
    userRecord: UserRecord;
    activities: Activity[];
}>();
</script>

<template>
    <Head :title="`User: ${userRecord.name}`" />

    <div class="p-6 space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">User Details</h1>
                <p class="text-sm text-muted-foreground">
                    View account information, roles, permissions, and change history.
                </p>
            </div>

            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link href="/admin/users">
                        Back
                    </Link>
                </Button>

                <Button as-child>
                    <Link :href="`/admin/users/${userRecord.id}/edit`">
                        Edit User
                    </Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-lg border bg-card p-6 shadow-sm lg:col-span-2">
                <h2 class="mb-4 text-lg font-semibold">Account Information</h2>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="text-sm font-medium text-muted-foreground">
                            Name
                        </div>
                        <div class="mt-1">
                            {{ userRecord.name }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm font-medium text-muted-foreground">
                            Username
                        </div>
                        <div class="mt-1">
                            {{ userRecord.username || '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm font-medium text-muted-foreground">
                            Email
                        </div>
                        <div class="mt-1">
                            {{ userRecord.email }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm font-medium text-muted-foreground">
                            Status
                        </div>
                        <div class="mt-1">
                            <span
                                :class="userRecord.is_disabled ? 'font-medium text-red-600' : 'font-medium text-green-600'"
                            >
                                {{ userRecord.is_disabled ? 'Disabled' : 'Active' }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <div class="text-sm font-medium text-muted-foreground">
                            Created
                        </div>
                        <div class="mt-1">
                            {{ userRecord.created_at || '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm font-medium text-muted-foreground">
                            Last Updated
                        </div>
                        <div class="mt-1">
                            {{ userRecord.updated_at || '—' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">Summary</h2>

                <div class="space-y-4">
                    <div>
                        <div class="text-sm font-medium text-muted-foreground">
                            Roles
                        </div>
                        <div class="mt-1 text-2xl font-semibold">
                            {{ userRecord.roles.length }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm font-medium text-muted-foreground">
                            Direct Permissions
                        </div>
                        <div class="mt-1 text-2xl font-semibold">
                            {{ userRecord.permissions.length }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm font-medium text-muted-foreground">
                            Total Permissions
                        </div>
                        <div class="mt-1 text-2xl font-semibold">
                            {{ userRecord.all_permissions_count }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-lg border bg-card p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Roles</h2>

            <div v-if="userRecord.roles.length" class="flex flex-wrap gap-2">
                <div
                    v-for="role in userRecord.roles"
                    :key="role.id"
                    class="rounded-md border px-3 py-2 text-sm"
                >
                    <div class="font-medium">
                        {{ role.label }}
                    </div>
                    <div class="font-mono text-xs text-muted-foreground">
                        {{ role.name }}
                    </div>
                </div>
            </div>

            <p v-else class="text-sm text-muted-foreground">
                This user does not have any assigned roles.
            </p>
        </div>

        <div class="rounded-lg border bg-card p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Direct Permissions</h2>

            <div v-if="userRecord.permissions.length" class="overflow-hidden rounded-md border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="p-3">Permission</th>
                            <th class="p-3">Name</th>
                            <th class="p-3">Group</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="permission in userRecord.permissions"
                            :key="permission.id"
                            class="border-b last:border-0"
                        >
                            <td class="p-3 font-medium">
                                {{ permission.label }}
                            </td>

                            <td class="p-3 font-mono text-xs">
                                {{ permission.name }}
                            </td>

                            <td class="p-3">
                                {{ permission.group_name || 'Ungrouped' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p v-else class="text-sm text-muted-foreground">
                This user does not have any direct permissions.
            </p>
        </div>

        <ActivityLog :activities="activities" />
        
    </div>
</template>
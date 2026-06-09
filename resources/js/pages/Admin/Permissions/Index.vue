<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

type Permission = {
    id: number;
    name: string;
    label: string;
    group_name: string | null;
    description: string | null;
    is_system: boolean;
    is_locked: boolean;
};

defineProps<{
    permissions: Record<string, Permission[]>;
}>();

function groupLabel(groupName: string): string {
    return groupName || 'Ungrouped';
}

function deletePermission(permission: Permission) {
    if (!confirm(`Delete permission "${permission.label}"?`)) {
        return;
    }

    router.delete(`/admin/permissions/${permission.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Permissions" />

    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Permissions</h1>
                <p class="text-sm text-muted-foreground">
                    Manage application permissions.
                </p>
            </div>

            <Button as-child>
                <Link href="/admin/permissions/create">
                    Add Permission
                </Link>
            </Button>
        </div>

        <div class="space-y-6">
            <div
                v-for="(groupPermissions, groupName) in permissions"
                :key="groupName"
                class="rounded-lg border bg-card p-6 shadow-sm"
            >
                <h2 class="mb-4 text-lg font-semibold">
                    {{ groupLabel(String(groupName)) }}
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="py-2 pr-4">Label</th>
                                <th class="py-2 pr-4">Name</th>
                                <th class="py-2 pr-4">Description</th>
                                <th class="py-2 pr-4">System</th>
                                <th class="py-2 pr-4">Locked</th>
                                <th class="py-2 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="permission in groupPermissions"
                                :key="permission.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4 font-medium">
                                    {{ permission.label }}
                                </td>

                                <td class="py-3 pr-4 font-mono text-xs">
                                    {{ permission.name }}
                                </td>

                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ permission.description }}
                                </td>

                                <td class="py-3 pr-4">
                                    {{ permission.is_system ? 'Yes' : 'No' }}
                                </td>

                                <td class="py-3 pr-4">
                                    {{ permission.is_locked ? 'Yes' : 'No' }}
                                </td>

                                <td class="py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button size="sm" variant="outline" as-child>
                                            <Link :href="`/admin/permissions/${permission.id}/edit`">
                                                Edit
                                            </Link>
                                        </Button>

                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            :disabled="permission.is_locked"
                                            @click="deletePermission(permission)"
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
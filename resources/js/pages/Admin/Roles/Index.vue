<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

type Role = {
    id: number;
    name: string;
    label: string;
    description: string | null;
    is_system: boolean;
    is_locked: boolean;
    permissions_count: number;
    users_count: number;
};

defineProps<{
    roles: Role[];
}>();

function deleteRole(role: Role) {
    if (!confirm(`Delete role "${role.label}"?`)) {
        return;
    }

    router.delete(`/admin/roles/${role.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Roles" />

    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Roles</h1>
                <p class="text-sm text-muted-foreground">
                    Manage security roles and their permissions.
                </p>
            </div>

            <Button as-child>
                <Link href="/admin/roles/create">
                    Add Role
                </Link>
            </Button>
        </div>

        <div class="rounded-lg border bg-card shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="p-4">Label</th>
                        <th class="p-4">Users</th>
                        <th class="p-4">Name</th>
                        <th class="p-4">Permissions</th>
                        <th class="p-4">System</th>
                        <th class="p-4">Locked</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="role in roles"
                        :key="role.id"
                        class="border-b last:border-0"
                    >
                        <td class="p-4 font-medium">
                            {{ role.label }}
                        </td>

                        <td class="p-4">
                            {{ role.users_count }} users
                        </td>

                        <td class="p-4 font-mono text-xs">
                            {{ role.name }}
                        </td>

                        <td class="p-4">
                            {{ role.permissions_count }}
                        </td>

                        <td class="p-4">
                            {{ role.is_system ? 'Yes' : 'No' }}
                        </td>

                        <td class="p-4">
                            {{ role.is_locked ? 'Yes' : 'No' }}
                        </td>

                        <td class="p-4">
                            <div class="flex justify-end gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    as-child
                                >
                                    <Link :href="`/admin/roles/${role.id}/edit`">
                                        Edit
                                    </Link>
                                </Button>

                                <Button
                                    size="sm"
                                    variant="destructive"
                                    :disabled="role.is_locked"
                                    @click="deleteRole(role)"
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
</template>
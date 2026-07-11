<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ActionToolbar from '@/Components/Admin/ActionToolbar.vue';
import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import DataTableEmpty from '@/Components/Tables/DataTableEmpty.vue';
import DataTableHeaderCell from '@/Components/Tables/DataTableHeaderCell.vue';
import { Button } from '@/components/ui/button';

import type { AdminRoleListItem } from '@/types/role';

defineProps<{
    roles: AdminRoleListItem[];
}>();

const selectedRole = ref<AdminRoleListItem | null>(null);
const deleteDialogOpen = ref(false);
const deleting = ref(false);

function requestDelete(role: AdminRoleListItem) {
    if (role.is_locked) {
        return;
    }

    selectedRole.value = role;
    deleteDialogOpen.value = true;
}

function cancelDelete() {
    deleteDialogOpen.value = false;
    selectedRole.value = null;
}

function confirmDelete() {
    if (!selectedRole.value || selectedRole.value.is_locked) {
        return;
    }

    deleting.value = true;

    router.delete(`/admin/roles/${selectedRole.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteDialogOpen.value = false;
            selectedRole.value = null;
        },
    });
}
</script>

<template>
    <Head title="Roles" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="Roles"
            description="Manage security roles and their permissions."
        />

        <ActionToolbar align="end">
            <template #secondary>
                <Button as-child>
                    <Link href="/admin/roles/create">
                        Add Role
                    </Link>
                </Button>
            </template>
        </ActionToolbar>

        <DataTable min-width="900px">
            <thead>
                <tr class="border-b bg-muted/30">
                    <DataTableHeaderCell label="Label" />
                    <DataTableHeaderCell label="Users" />
                    <DataTableHeaderCell label="Name" />
                    <DataTableHeaderCell label="Permissions" />
                    <DataTableHeaderCell label="System" />
                    <DataTableHeaderCell label="Locked" />
                    <DataTableHeaderCell label="Actions" align="right" />
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="role in roles"
                    :key="role.id"
                    class="border-b last:border-0 hover:bg-muted/20"
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
                        <StatusBadge
                            :status="role.is_system ? 'system' : 'custom'"
                            :label="role.is_system ? 'Yes' : 'No'"
                            :tone="role.is_system ? 'info' : 'neutral'"
                        />
                    </td>

                    <td class="p-4">
                        <StatusBadge
                            :status="role.is_locked ? 'locked' : 'unlocked'"
                            :label="role.is_locked ? 'Yes' : 'No'"
                            :tone="role.is_locked ? 'warning' : 'neutral'"
                        />
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
                                @click="requestDelete(role)"
                            >
                                Delete
                            </Button>
                        </div>
                    </td>
                </tr>

                <DataTableEmpty
                    v-if="roles.length === 0"
                    :colspan="7"
                    message="No roles found."
                />
            </tbody>
        </DataTable>

        <ConfirmActionDialog
            v-model:open="deleteDialogOpen"
            title="Delete role?"
            :description="
                selectedRole
                    ? `Delete the role '${selectedRole.label}'? This action cannot be undone.`
                    : 'This action cannot be undone.'
            "
            confirm-label="Delete Role"
            destructive
            :loading="deleting"
            :disabled="selectedRole?.is_locked ?? true"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </div>
</template>

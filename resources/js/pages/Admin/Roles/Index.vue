<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

import ActionToolbar from '@/Components/Admin/ActionToolbar.vue';
import AdminRowActions from '@/Components/Admin/AdminRowActions.vue';
import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import DataTableEmpty from '@/Components/Tables/DataTableEmpty.vue';
import DataTableHeaderCell from '@/Components/Tables/DataTableHeaderCell.vue';
import { Button } from '@/components/ui/button';
import { useDeleteConfirmation } from '@/composables/useDeleteConfirmation';

import type { AdminRoleListItem } from '@/types/role';

defineProps<{
    roles: AdminRoleListItem[];
}>();

const deletion = useDeleteConfirmation<AdminRoleListItem>();

function requestDelete(role: AdminRoleListItem) {
    if (!role.is_locked) {
        deletion.requestDelete(role);
    }
}

function confirmDelete() {
    deletion.runDelete((role, finish) => {
        if (role.is_locked) {
            finish();

            return;
        }

        router.delete(`/admin/roles/${role.id}`, {
            preserveScroll: true,
            onFinish: finish,
        });
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

        <DataTable
            min-width="900px"
            caption="Security roles"
        >
            <thead>
                <tr class="border-b bg-muted/30">
                    <DataTableHeaderCell label="Label" />
                    <DataTableHeaderCell label="Users" />
                    <DataTableHeaderCell label="Name" />
                    <DataTableHeaderCell label="Permissions" />
                    <DataTableHeaderCell label="System" />
                    <DataTableHeaderCell label="Locked" />
                    <DataTableHeaderCell
                        label="Actions"
                        align="right"
                    />
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
                        {{ role.users_count }}
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
                            context="System role"
                        />
                    </td>

                    <td class="p-4">
                        <StatusBadge
                            :status="role.is_locked ? 'locked' : 'unlocked'"
                            :label="role.is_locked ? 'Yes' : 'No'"
                            :tone="role.is_locked ? 'warning' : 'neutral'"
                            context="Locked"
                        />
                    </td>

                    <td class="p-4">
                        <AdminRowActions
                            compact
                            :view-href="null"
                            :edit-href="`/admin/roles/${role.id}/edit`"
                            :delete-disabled="role.is_locked"
                            @delete="requestDelete(role)"
                        />
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
            v-model:open="deletion.open.value"
            title="Delete role?"
            :description="
                deletion.selected.value
                    ? `Delete the role '${deletion.selected.value.label}'? This action cannot be undone.`
                    : 'This action cannot be undone.'
            "
            confirm-label="Delete Role"
            destructive
            :loading="deletion.processing.value"
            :disabled="
                deletion.selected.value?.is_locked
                ?? true
            "
            @confirm="confirmDelete"
            @cancel="deletion.cancelDelete"
        />
    </div>
</template>

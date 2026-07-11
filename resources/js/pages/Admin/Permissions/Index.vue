<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

import ActionToolbar from '@/Components/Admin/ActionToolbar.vue';
import PermissionTableGroup from '@/Components/Admin/PermissionTableGroup.vue';
import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { useDeleteConfirmation } from '@/composables/useDeleteConfirmation';

import type {
    AdminPermission,
    GroupedAdminPermissions,
} from '@/types/permission';

defineProps<{
    permissions: GroupedAdminPermissions;
}>();

const deletion = useDeleteConfirmation<AdminPermission>();

function groupLabel(groupName: string): string {
    return groupName || 'Ungrouped';
}

function requestDelete(permission: AdminPermission) {
    if (!permission.is_locked) {
        deletion.requestDelete(permission);
    }
}

function confirmDelete() {
    deletion.runDelete((permission, finish) => {
        if (permission.is_locked) {
            finish();
            return;
        }

        router.delete(
            `/admin/permissions/${permission.id}`,
            {
                preserveScroll: true,
                onFinish: finish,
            },
        );
    });
}
</script>

<template>
    <Head title="Permissions" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="Permissions"
            description="Manage application permissions."
        />

        <ActionToolbar align="end">
            <template #secondary>
                <Button as-child>
                    <Link href="/admin/permissions/create">
                        Add Permission
                    </Link>
                </Button>
            </template>
        </ActionToolbar>

        <div
            v-if="Object.keys(permissions).length"
            class="space-y-8"
        >
            <PermissionTableGroup
                v-for="(groupPermissions, groupName) in permissions"
                :key="groupName"
                :title="groupLabel(String(groupName))"
                :permissions="groupPermissions"
                @delete="requestDelete"
            />
        </div>

        <div
            v-else
            class="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground"
            role="status"
        >
            No permissions found.
        </div>

        <ConfirmActionDialog
            v-model:open="deletion.open.value"
            title="Delete permission?"
            :description="
                deletion.selected.value
                    ? `Delete the permission '${deletion.selected.value.label}'? This action cannot be undone.`
                    : 'This action cannot be undone.'
            "
            confirm-label="Delete Permission"
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

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ActionToolbar from '@/Components/Admin/ActionToolbar.vue';
import PermissionTableGroup from '@/Components/Admin/PermissionTableGroup.vue';
import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';

import type {
    AdminPermission,
    GroupedAdminPermissions,
} from '@/types/permission';

defineProps<{
    permissions: GroupedAdminPermissions;
}>();

const selectedPermission = ref<AdminPermission | null>(null);
const deleteDialogOpen = ref(false);
const deleting = ref(false);

function groupLabel(groupName: string): string {
    return groupName || 'Ungrouped';
}

function requestDelete(permission: AdminPermission) {
    if (permission.is_locked) {
        return;
    }

    selectedPermission.value = permission;
    deleteDialogOpen.value = true;
}

function cancelDelete() {
    deleteDialogOpen.value = false;
    selectedPermission.value = null;
}

function confirmDelete() {
    if (!selectedPermission.value || selectedPermission.value.is_locked) {
        return;
    }

    deleting.value = true;

    router.delete(
        `/admin/permissions/${selectedPermission.value.id}`,
        {
            preserveScroll: true,
            onFinish: () => {
                deleting.value = false;
                deleteDialogOpen.value = false;
                selectedPermission.value = null;
            },
        },
    );
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

        <div class="space-y-8">
            <PermissionTableGroup
                v-for="(groupPermissions, groupName) in permissions"
                :key="groupName"
                :title="groupLabel(String(groupName))"
                :permissions="groupPermissions"
                @delete="requestDelete"
            />
        </div>

        <ConfirmActionDialog
            v-model:open="deleteDialogOpen"
            title="Delete permission?"
            :description="
                selectedPermission
                    ? `Delete the permission '${selectedPermission.label}'? This action cannot be undone.`
                    : 'This action cannot be undone.'
            "
            confirm-label="Delete Permission"
            destructive
            :loading="deleting"
            :disabled="selectedPermission?.is_locked ?? true"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </div>
</template>

<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';

import PermissionGroupCard from '@/Components/Admin/PermissionGroupCard.vue';
import FormActions from '@/Components/Forms/FormActions.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormGrid from '@/Components/Forms/FormGrid.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { Input } from '@/components/ui/input';

import type {
    AdminRoleDetail,
    GroupedPermissions,
} from '@/types/role';

const props = defineProps<{
    role: AdminRoleDetail;
    permissions: GroupedPermissions;
}>();

const form = useForm({
    name: props.role.name,
    label: props.role.label,
    description: props.role.description ?? '',
    permissions: props.role.permissions.map(
        (permission) => permission.id,
    ),
});

function togglePermission(
    permissionId: number,
    checked: boolean,
) {
    if (checked) {
        if (!form.permissions.includes(permissionId)) {
            form.permissions.push(permissionId);
        }

        return;
    }

    form.permissions = form.permissions.filter(
        (id) => id !== permissionId,
    );
}

function submit() {
    form.put(`/admin/roles/${props.role.id}`, {
        preserveScroll: true,
    });
}

function cancel() {
    router.visit('/admin/roles');
}
</script>

<template>
    <Head title="Edit Role" />

    <div class="space-y-8 p-6">
        <PageHeader
            title="Edit Role"
            description="Update role details and assigned permissions."
        />

        <form
            class="space-y-8"
            @submit.prevent="submit"
        >
            <FormSection
                title="Role Details"
                description="Update the role's label, internal name, and purpose."
            >
                <FormGrid :columns="2">
                    <FormField
                        label="Label"
                        for-id="label"
                        required
                        :error="form.errors.label"
                        v-slot="{ errorId, invalid }"
                    >
                        <Input
                            id="label"
                            v-model="form.label"
                            :aria-invalid="invalid || undefined"
                            :aria-describedby="errorId"
                        />
                    </FormField>

                    <FormField
                        label="Name"
                        for-id="name"
                        required
                        :error="form.errors.name"
                        v-slot="{ errorId, invalid }"
                    >
                        <Input
                            id="name"
                            v-model="form.name"
                            :aria-invalid="invalid || undefined"
                            :aria-describedby="errorId"
                        />
                    </FormField>
                </FormGrid>

                <div class="mt-6">
                    <FormField
                        label="Description"
                        for-id="description"
                        :error="form.errors.description"
                        v-slot="{ errorId, invalid }"
                    >
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                            :aria-invalid="invalid || undefined"
                            :aria-describedby="errorId"
                        />
                    </FormField>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <StatusBadge
                        :status="role.is_system ? 'system' : 'custom'"
                        :label="
                            role.is_system
                                ? 'System Role'
                                : 'Custom Role'
                        "
                        :tone="role.is_system ? 'info' : 'neutral'"
                        size="md"
                    />

                    <StatusBadge
                        :status="role.is_locked ? 'locked' : 'unlocked'"
                        :label="
                            role.is_locked
                                ? 'Locked'
                                : 'Unlocked'
                        "
                        :tone="role.is_locked ? 'warning' : 'neutral'"
                        size="md"
                    />
                </div>
            </FormSection>

            <FormSection
                title="Permissions"
                description="Select the permissions this role should have."
            >
                <div class="space-y-6">
                    <PermissionGroupCard
                        v-for="(groupPermissions, groupName) in permissions"
                        :key="groupName"
                        :title="String(groupName || 'Ungrouped')"
                        :permissions="groupPermissions"
                        :selected-permission-ids="form.permissions"
                        :disabled="form.processing"
                        @toggle="togglePermission"
                    />
                </div>
            </FormSection>

            <FormActions
                submit-label="Save Changes"
                processing-label="Saving Role..."
                :processing="form.processing"
                @submit="submit"
                @cancel="cancel"
            />
        </form>
    </div>
</template>

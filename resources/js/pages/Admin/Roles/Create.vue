<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';

import PermissionGroupCard from '@/Components/Admin/PermissionGroupCard.vue';
import FormActions from '@/Components/Forms/FormActions.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormGrid from '@/Components/Forms/FormGrid.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Input } from '@/components/ui/input';

import type { GroupedPermissions } from '@/types/role';

defineProps<{
    permissions: GroupedPermissions;
}>();

const form = useForm({
    name: '',
    label: '',
    description: '',
    permissions: [] as number[],
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
    form.post('/admin/roles', {
        preserveScroll: true,
    });
}

function cancel() {
    router.visit('/admin/roles');
}
</script>

<template>
    <Head title="Create Role" />

    <div class="space-y-8 p-6">
        <PageHeader
            title="Create Role"
            description="Add a role and assign permissions."
        />

        <form
            class="space-y-8"
            @submit.prevent="submit"
        >
            <FormSection
                title="Role Details"
                description="Define the role's public label, internal name, and purpose."
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
                        description="Example: editor"
                        :error="form.errors.name"
                        v-slot="{ descriptionId, errorId, invalid }"
                    >
                        <Input
                            id="name"
                            v-model="form.name"
                            :aria-invalid="invalid || undefined"
                            :aria-describedby="
                                [descriptionId, errorId]
                                    .filter(Boolean)
                                    .join(' ') || undefined
                            "
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
                submit-label="Create Role"
                processing-label="Creating Role..."
                :processing="form.processing"
                @submit="submit"
                @cancel="cancel"
            />
        </form>
    </div>
</template>

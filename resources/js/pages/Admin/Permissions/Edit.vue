<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';

import FormActions from '@/Components/Forms/FormActions.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormGrid from '@/Components/Forms/FormGrid.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';

import type { AdminPermission } from '@/types/permission';

const props = defineProps<{
    permission: AdminPermission;
}>();

const form = useForm({
    name: props.permission.name,
    label: props.permission.label,
    group_name: props.permission.group_name ?? '',
    description: props.permission.description ?? '',
    is_system: props.permission.is_system,
    is_locked: props.permission.is_locked,
});

function submit() {
    form.put(`/admin/permissions/${props.permission.id}`, {
        preserveScroll: true,
    });
}

function cancel() {
    router.visit('/admin/permissions');
}
</script>

<template>
    <Head title="Edit Permission" />

    <div class="space-y-8 p-6">
        <PageHeader
            title="Edit Permission"
            description="Update an application permission."
        />

        <form
            class="space-y-8"
            @submit.prevent="submit"
        >
            <FormSection
                title="Permission Details"
                description="Update the permission's label, internal name, group, and purpose."
            >
                <FormGrid :columns="2">
                    <FormField
                        label="Label"
                        for-id="label"
                        required
                        :error="form.errors.label"
                    >
                        <Input
                            id="label"
                            v-model="form.label"
                        />
                    </FormField>

                    <FormField
                        label="Name"
                        for-id="name"
                        required
                        :error="form.errors.name"
                    >
                        <Input
                            id="name"
                            v-model="form.name"
                        />
                    </FormField>

                    <FormField
                        label="Group"
                        for-id="group_name"
                        :error="form.errors.group_name"
                    >
                        <Input
                            id="group_name"
                            v-model="form.group_name"
                        />
                    </FormField>
                </FormGrid>

                <div class="mt-6">
                    <FormField
                        label="Description"
                        for-id="description"
                        :error="form.errors.description"
                    >
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                        />
                    </FormField>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-6">
                    <label class="flex items-center gap-2 text-sm">
                        <Checkbox
                            :checked="form.is_system"
                            @update:checked="form.is_system = Boolean($event)"
                        />
                        System
                    </label>

                    <label class="flex items-center gap-2 text-sm">
                        <Checkbox
                            :checked="form.is_locked"
                            @update:checked="form.is_locked = Boolean($event)"
                        />
                        Locked
                    </label>

                    <StatusBadge
                        :status="permission.is_locked ? 'locked' : 'unlocked'"
                        :label="permission.is_locked ? 'Currently Locked' : 'Currently Unlocked'"
                        :tone="permission.is_locked ? 'warning' : 'neutral'"
                    />
                </div>
            </FormSection>

            <FormActions
                submit-label="Save Permission"
                :processing="form.processing"
                @submit="submit"
                @cancel="cancel"
            />
        </form>
    </div>
</template>

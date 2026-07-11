<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';

import FormActions from '@/Components/Forms/FormActions.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormGrid from '@/Components/Forms/FormGrid.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';

const form = useForm({
    name: '',
    label: '',
    group_name: '',
    description: '',
    is_system: false,
    is_locked: false,
});

function submit() {
    form.post('/admin/permissions', {
        preserveScroll: true,
    });
}

function cancel() {
    router.visit('/admin/permissions');
}
</script>

<template>
    <Head title="Create Permission" />

    <div class="space-y-8 p-6">
        <PageHeader
            title="Create Permission"
            description="Add a new application permission."
        />

        <form
            class="space-y-8"
            @submit.prevent="submit"
        >
            <FormSection
                title="Permission Details"
                description="Define the permission's label, internal name, group, and purpose."
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
                        description="Example: manage_images"
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

                <div class="mt-6 flex flex-wrap gap-6">
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
                </div>
            </FormSection>

            <FormActions
                submit-label="Create Permission"
                :processing="form.processing"
                @submit="submit"
                @cancel="cancel"
            />
        </form>
    </div>
</template>

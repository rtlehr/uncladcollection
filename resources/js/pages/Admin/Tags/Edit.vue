<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';

import FormActions from '@/Components/Forms/FormActions.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormGrid from '@/Components/Forms/FormGrid.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import DetailRow from '@/Components/Shared/DetailRow.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Input } from '@/components/ui/input';

import type { AdminTag } from '@/types/tag';

const props = defineProps<{
    tag: AdminTag;
}>();

const form = useForm({
    name: props.tag.name,
    tag_type: props.tag.tag_type,
    description: props.tag.description ?? '',
});

function submit() {
    form.put(`/admin/tags/${props.tag.id}`, {
        preserveScroll: true,
    });
}

function cancel() {
    router.visit('/admin/tags');
}
</script>

<template>
    <Head title="Edit Tag" />

    <div class="space-y-8 p-6">
        <PageHeader
            title="Edit Tag"
            description="Update an existing image or blog tag."
        />

        <form
            class="space-y-8"
            @submit.prevent="submit"
        >
            <FormSection
                title="Tag Details"
                description="Update the tag name, content type, and optional description."
            >
                <FormGrid :columns="2">
                    <FormField
                        label="Name"
                        for-id="name"
                        required
                        :error="form.errors.name"
                    >
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Enter tag name"
                        />
                    </FormField>

                    <FormField
                        label="Type"
                        for-id="tag_type"
                        required
                        :error="form.errors.tag_type"
                    >
                        <select
                            id="tag_type"
                            v-model="form.tag_type"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="image">
                                Image
                            </option>

                            <option value="blog">
                                Blog
                            </option>
                        </select>
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
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Optional description"
                        />
                    </FormField>
                </div>

                <div class="mt-6 rounded-lg border bg-muted/20 p-4">
                    <DetailRow
                        label="Slug"
                        :value="tag.slug"
                        break-all
                    />
                </div>
            </FormSection>

            <FormActions
                submit-label="Save Changes"
                :processing="form.processing"
                @submit="submit"
                @cancel="cancel"
            />
        </form>
    </div>
</template>

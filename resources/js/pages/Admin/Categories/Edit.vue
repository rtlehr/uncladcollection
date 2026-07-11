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

import type { AdminCategory } from '@/types/category';

const props = defineProps<{
    category: AdminCategory;
}>();

const form = useForm({
    name: props.category.name,
    slug: props.category.slug,
    description: props.category.description ?? '',
    category_type: props.category.category_type,
    sort_order: props.category.sort_order,
    is_active: props.category.is_active,
});

function submit() {
    form.put(`/admin/categories/${props.category.id}`, {
        preserveScroll: true,
    });
}

function cancel() {
    router.visit('/admin/categories');
}
</script>

<template>
    <Head title="Edit Category" />

    <div class="space-y-8 p-6">
        <PageHeader
            title="Edit Category"
            description="Update this category."
        />

        <form
            class="space-y-8"
            @submit.prevent="submit"
        >
            <FormSection
                title="Category Details"
                description="Update the category name, URL slug, content type, and display order."
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
                            placeholder="Category name"
                        />
                    </FormField>

                    <FormField
                        label="Slug"
                        for-id="slug"
                        required
                        description="Used in URLs. Example: image-lifestyle or blog-travel."
                        :error="form.errors.slug"
                    >
                        <Input
                            id="slug"
                            v-model="form.slug"
                            placeholder="category-slug"
                        />
                    </FormField>

                    <FormField
                        label="Type"
                        for-id="category_type"
                        required
                        :error="form.errors.category_type"
                    >
                        <select
                            id="category_type"
                            v-model="form.category_type"
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

                    <FormField
                        label="Sort Order"
                        for-id="sort_order"
                        description="Lower numbers appear first."
                        :error="form.errors.sort_order"
                    >
                        <Input
                            id="sort_order"
                            v-model="form.sort_order"
                            type="number"
                            min="0"
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
                            placeholder="Optional category description"
                        />
                    </FormField>
                </div>

                <div class="mt-6 rounded-lg border bg-muted/20 p-4">
                    <label class="flex items-start gap-3">
                        <Checkbox
                            :checked="form.is_active"
                            @update:checked="form.is_active = Boolean($event)"
                        />

                        <div class="flex-1">
                            <div class="font-medium">
                                Active
                            </div>

                            <div class="text-sm text-muted-foreground">
                                Inactive categories can be hidden from public lists.
                            </div>
                        </div>

                        <StatusBadge
                            :status="form.is_active ? 'active' : 'inactive'"
                        />
                    </label>
                </div>
            </FormSection>

            <FormActions
                submit-label="Save Category"
                :processing="form.processing"
                @submit="submit"
                @cancel="cancel"
            />
        </form>
    </div>
</template>

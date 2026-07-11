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
    slug: '',
    description: '',
    category_type: 'image',
    sort_order: 0,
    is_active: true,
});

function generateSlug() {
    form.slug = form.name
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function submit() {
    form.post('/admin/categories', {
        preserveScroll: true,
    });
}

function cancel() {
    router.visit('/admin/categories');
}
</script>

<template>
    <Head title="Create Category" />

    <div class="space-y-8 p-6">
        <PageHeader
            title="Create Category"
            description="Create a new image or blog category."
        />

        <form
            class="space-y-8"
            @submit.prevent="submit"
        >
            <FormSection
                title="Category Details"
                description="Define the category name, URL slug, content type, and display order."
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
                            @blur="generateSlug"
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

                        <div>
                            <div class="font-medium">
                                Active
                            </div>

                            <div class="text-sm text-muted-foreground">
                                Active categories will be available throughout the site.
                            </div>
                        </div>
                    </label>
                </div>
            </FormSection>

            <FormActions
                submit-label="Create Category"
                processing-label="Creating..."
                :processing="form.processing"
                @submit="submit"
                @cancel="cancel"
            />
        </form>
    </div>
</template>

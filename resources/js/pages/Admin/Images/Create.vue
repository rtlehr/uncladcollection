<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';

import OptionChecklist from '@/Components/Admin/OptionChecklist.vue';
import FormActions from '@/Components/Forms/FormActions.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormGrid from '@/Components/Forms/FormGrid.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';

import type { AdminImageOption } from '@/types/adminImageForm';

defineProps<{
    collections: AdminImageOption[];
    categories: AdminImageOption[];
    tags: AdminImageOption[];
}>();

const previewUrl = ref<string | null>(null);

const form = useForm({
    collection_id: '' as string | number,
    title: '',
    description: '',
    photographer: '',
    sort_order: 0,
    is_active: true,
    is_ai_generated: false,
    image: null as File | null,
    categories: [] as number[],
    tags: [] as number[],
});

function revokePreview() {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
}

function handleImageChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    revokePreview();
    form.image = file;
    previewUrl.value = file ? URL.createObjectURL(file) : null;
}

function toggleSelection(
    field: 'categories' | 'tags',
    id: number,
    checked: boolean,
) {
    form[field] = checked
        ? [...form[field], id]
        : form[field].filter((selectedId) => selectedId !== id);
}

function submit() {
    form.transform((data) => ({
        ...data,
        collection_id: data.collection_id === '' ? null : data.collection_id,
    })).post('/admin/images', {
        forceFormData: true,
        preserveScroll: true,
    });
}

function cancel() {
    router.visit('/admin/images');
}

onBeforeUnmount(revokePreview);
</script>

<template>
    <Head title="Create Image" />

    <div class="space-y-8 p-6">
        <PageHeader
            title="Create Image"
            description="Upload a new image and assign it to collections, categories, and tags."
        />

        <form class="space-y-8" @submit.prevent="submit">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
                <div class="space-y-6">
                    <FormSection
                        title="Image Details"
                        description="Public-facing image title, attribution, and description."
                    >
                        <FormGrid :columns="2">
                            <FormField
                                label="Title"
                                for-id="title"
                                required
                                :error="form.errors.title"
                            >
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    placeholder="Enter image title"
                                />
                            </FormField>

                            <FormField
                                label="Photographer"
                                for-id="photographer"
                                :error="form.errors.photographer"
                            >
                                <Input
                                    id="photographer"
                                    v-model="form.photographer"
                                    placeholder="Optional photographer name"
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
                                    rows="5"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="Optional image description"
                                />
                            </FormField>
                        </div>
                    </FormSection>

                    <FormSection
                        title="Upload"
                        description="Upload the source image used to generate marketplace variants."
                    >
                        <FormField
                            label="Image File"
                            for-id="image"
                            required
                            description="The physical file is stored on the server; the database stores file paths."
                            :error="form.errors.image"
                        >
                            <Input
                                id="image"
                                type="file"
                                accept="image/*"
                                @change="handleImageChange"
                            />
                        </FormField>

                        <div v-if="previewUrl" class="mt-6">
                            <div class="mb-2 text-sm font-medium">
                                Preview
                            </div>

                            <div class="rounded-lg border bg-muted p-4">
                                <img
                                    :src="previewUrl"
                                    alt="Image preview"
                                    class="max-h-96 w-full rounded object-contain"
                                />
                            </div>
                        </div>
                    </FormSection>

                    <FormSection
                        title="Categories"
                        description="Select the image categories used for browsing."
                    >
                        <OptionChecklist
                            :options="categories"
                            :selected-ids="form.categories"
                            empty-message="No image categories are available."
                            :disabled="form.processing"
                            @toggle="(id, checked) => toggleSelection('categories', id, checked)"
                        />
                    </FormSection>

                    <FormSection
                        title="Tags"
                        description="Select search and discovery tags."
                    >
                        <OptionChecklist
                            :options="tags"
                            :selected-ids="form.tags"
                            empty-message="No image tags are available."
                            :disabled="form.processing"
                            @toggle="(id, checked) => toggleSelection('tags', id, checked)"
                        />
                    </FormSection>
                </div>

                <div class="space-y-6">
                    <FormSection
                        title="Publishing"
                        description="Control image organization and public visibility."
                    >
                        <div class="space-y-6">
                            <FormField
                                label="Collection"
                                for-id="collection_id"
                                :error="form.errors.collection_id"
                            >
                                <select
                                    id="collection_id"
                                    v-model="form.collection_id"
                                    class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                >
                                    <option value="">
                                        No Collection
                                    </option>

                                    <option
                                        v-for="collection in collections"
                                        :key="collection.id"
                                        :value="collection.id"
                                    >
                                        {{ collection.name }}
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

                            <label class="flex items-start gap-3 rounded-md border p-4">
                                <Checkbox
                                    :checked="form.is_active"
                                    @update:checked="form.is_active = Boolean($event)"
                                />

                                <div>
                                    <div class="text-sm font-medium">
                                        Active
                                    </div>

                                    <p class="text-xs text-muted-foreground">
                                        Active images can be displayed on the public site.
                                    </p>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 rounded-md border p-4">
                                <Checkbox
                                    :checked="form.is_ai_generated"
                                    @update:checked="form.is_ai_generated = Boolean($event)"
                                />

                                <div>
                                    <div class="text-sm font-medium">
                                        AI Generated
                                    </div>

                                    <p class="text-xs text-muted-foreground">
                                        Mark images created or significantly assisted by AI.
                                    </p>
                                </div>
                            </label>
                        </div>
                    </FormSection>
                </div>
            </div>

            <FormActions
                submit-label="Create Image"
                processing-label="Uploading..."
                :processing="form.processing"
                @submit="submit"
                @cancel="cancel"
            />
        </form>
    </div>
</template>

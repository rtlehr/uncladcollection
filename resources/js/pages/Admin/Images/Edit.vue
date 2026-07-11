<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';

import OptionChecklist from '@/Components/Admin/OptionChecklist.vue';
import FormActions from '@/Components/Forms/FormActions.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormGrid from '@/Components/Forms/FormGrid.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import DetailRow from '@/Components/Shared/DetailRow.vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';

import type {
    AdminEditableImage,
    AdminImageOption,
} from '@/types/adminImageForm';

const props = defineProps<{
    imageRecord: AdminEditableImage;
    collections: AdminImageOption[];
    categories: AdminImageOption[];
    tags: AdminImageOption[];
}>();

const previewUrl = ref<string | null>(props.imageRecord.thumbnail_url);
let temporaryPreviewUrl: string | null = null;

const form = useForm({
    collection_id: props.imageRecord.collection_id ?? '' as string | number,
    title: props.imageRecord.title,
    description: props.imageRecord.description ?? '',
    photographer: props.imageRecord.photographer ?? '',
    sort_order: props.imageRecord.sort_order,
    is_active: props.imageRecord.is_active,
    is_ai_generated: props.imageRecord.is_ai_generated,
    downloads_count: props.imageRecord.downloads_count,
    favorites_count: props.imageRecord.favorites_count,
    purchases_count: props.imageRecord.purchases_count,
    views_count: props.imageRecord.views_count,
    image: null as File | null,
    categories: [...props.imageRecord.categories],
    tags: [...props.imageRecord.tags],
});

function revokeTemporaryPreview() {
    if (temporaryPreviewUrl) {
        URL.revokeObjectURL(temporaryPreviewUrl);
        temporaryPreviewUrl = null;
    }
}

function handleImageChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    revokeTemporaryPreview();
    form.image = file;

    if (file) {
        temporaryPreviewUrl = URL.createObjectURL(file);
        previewUrl.value = temporaryPreviewUrl;
    } else {
        previewUrl.value = props.imageRecord.thumbnail_url;
    }
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
        _method: 'put',
    })).post(`/admin/images/${props.imageRecord.id}`, {
        forceFormData: true,
        preserveScroll: true,
    });
}

function cancel() {
    router.visit('/admin/images');
}

onBeforeUnmount(revokeTemporaryPreview);
</script>

<template>
    <Head title="Edit Image" />

    <div class="space-y-8 p-6">
        <PageHeader
            title="Edit Image"
            description="Update image details, replace the uploaded file, and manage categories and tags."
        />

        <form class="space-y-8" @submit.prevent="submit">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
                <div class="space-y-6">
                    <FormSection
                        title="Image Details"
                        description="Update the public-facing image information."
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

                        <div class="mt-6 rounded-lg border bg-muted/20 p-4">
                            <DetailRow
                                label="Slug"
                                :value="imageRecord.slug"
                                break-all
                            />
                        </div>

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
                        title="Image File"
                        description="Preview or replace the uploaded source image."
                    >
                        <div
                            v-if="previewUrl"
                            class="mb-6 rounded-lg border bg-muted p-4"
                        >
                            <img
                                :src="previewUrl"
                                :alt="form.title"
                                class="max-h-96 w-full rounded object-contain"
                            />
                        </div>

                        <FormField
                            label="Replace Image"
                            for-id="image"
                            description="Leave blank to keep the current image."
                            :error="form.errors.image"
                        >
                            <Input
                                id="image"
                                type="file"
                                accept="image/*"
                                @change="handleImageChange"
                            />
                        </FormField>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="flex items-center justify-between gap-3 rounded-md border p-3">
                                <span class="text-sm font-medium">Original</span>
                                <StatusBadge
                                    :status="imageRecord.original_url ? 'available' : 'missing'"
                                    :label="imageRecord.original_url ? 'Available' : 'Missing'"
                                    :tone="imageRecord.original_url ? 'success' : 'danger'"
                                />
                            </div>

                            <div class="flex items-center justify-between gap-3 rounded-md border p-3">
                                <span class="text-sm font-medium">High Res</span>
                                <StatusBadge
                                    :status="imageRecord.high_res_url ? 'available' : 'missing'"
                                    :label="imageRecord.high_res_url ? 'Available' : 'Missing'"
                                    :tone="imageRecord.high_res_url ? 'success' : 'danger'"
                                />
                            </div>

                            <div class="flex items-center justify-between gap-3 rounded-md border p-3">
                                <span class="text-sm font-medium">Thumbnail</span>
                                <StatusBadge
                                    :status="imageRecord.thumbnail_url ? 'available' : 'missing'"
                                    :label="imageRecord.thumbnail_url ? 'Available' : 'Missing'"
                                    :tone="imageRecord.thumbnail_url ? 'success' : 'danger'"
                                />
                            </div>

                            <div class="flex items-center justify-between gap-3 rounded-md border p-3">
                                <span class="text-sm font-medium">Icon</span>
                                <StatusBadge
                                    :status="imageRecord.icon_url ? 'available' : 'missing'"
                                    :label="imageRecord.icon_url ? 'Available' : 'Missing'"
                                    :tone="imageRecord.icon_url ? 'success' : 'danger'"
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
                                        Active images can be displayed publicly.
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

                    <FormSection
                        title="Statistics"
                        description="Administrative marketplace counters."
                    >
                        <div class="grid gap-4">
                            <FormField
                                label="Downloads"
                                for-id="downloads_count"
                                :error="form.errors.downloads_count"
                            >
                                <Input
                                    id="downloads_count"
                                    v-model="form.downloads_count"
                                    type="number"
                                    min="0"
                                />
                            </FormField>

                            <FormField
                                label="Favorites"
                                for-id="favorites_count"
                                :error="form.errors.favorites_count"
                            >
                                <Input
                                    id="favorites_count"
                                    v-model="form.favorites_count"
                                    type="number"
                                    min="0"
                                />
                            </FormField>

                            <FormField
                                label="Purchases"
                                for-id="purchases_count"
                                :error="form.errors.purchases_count"
                            >
                                <Input
                                    id="purchases_count"
                                    v-model="form.purchases_count"
                                    type="number"
                                    min="0"
                                />
                            </FormField>

                            <FormField
                                label="Views"
                                for-id="views_count"
                                :error="form.errors.views_count"
                            >
                                <Input
                                    id="views_count"
                                    v-model="form.views_count"
                                    type="number"
                                    min="0"
                                />
                            </FormField>
                        </div>
                    </FormSection>
                </div>
            </div>

            <FormActions
                submit-label="Save Image"
                :processing="form.processing"
                @submit="submit"
                @cancel="cancel"
            />
        </form>
    </div>
</template>

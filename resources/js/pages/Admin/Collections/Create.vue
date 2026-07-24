<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';

import FormActions from '@/Components/Forms/FormActions.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormGrid from '@/Components/Forms/FormGrid.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import CollectionCoverEditor from '@/components/admin/collections/CollectionCoverEditor.vue';
import type { ImageEditData } from '@/components/media/ImageEditorDialog.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';

const form = useForm({
    name: '',
    description: '',
    sort_order: 0,
    is_active: true,
    cover_original: null as File | null,
    cover_image: null as File | null,
    cover_edit_data: '',
    remove_cover_image: false,
});

function applyCover(payload: {
    original: File | null;
    rendered: File;
    edit: ImageEditData;
}): void {
    form.cover_original = payload.original;
    form.cover_image = payload.rendered;
    form.cover_edit_data = JSON.stringify(payload.edit);
    form.remove_cover_image = false;
}

function removeCover(): void {
    form.cover_original = null;
    form.cover_image = null;
    form.cover_edit_data = '';
    form.remove_cover_image = true;
}

function submit(): void {
    form.post('/admin/collections', {
        forceFormData: true,
        preserveScroll: true,
    });
}

function cancel(): void {
    router.visit('/admin/collections');
}
</script>

<template>
    <Head title="Create Collection" />

    <div class="space-y-8 p-6">
        <PageHeader
            title="Create Collection"
            description="Create a new image collection."
        />

        <form class="space-y-8" @submit.prevent="submit">
            <FormSection
                title="Collection Details"
                description="Define the collection name, description, display order, and active state."
            >
                <FormGrid :columns="2">
                    <FormField label="Name" for-id="name" required :error="form.errors.name">
                        <Input id="name" v-model="form.name" placeholder="Enter collection name" />
                    </FormField>

                    <FormField
                        label="Sort Order"
                        for-id="sort_order"
                        description="Lower numbers appear first."
                        :error="form.errors.sort_order"
                    >
                        <Input id="sort_order" v-model="form.sort_order" type="number" min="0" />
                    </FormField>
                </FormGrid>

                <div class="mt-6">
                    <FormField label="Description" for-id="description" :error="form.errors.description">
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
                    <label for="is_active" class="flex cursor-pointer items-start gap-3">
                        <Checkbox
                            id="is_active"
                            :model-value="form.is_active"
                            @update:model-value="form.is_active = $event === true"
                        />
                        <div>
                            <div class="font-medium">Active</div>
                            <div class="text-sm text-muted-foreground">
                                Active collections will be available throughout the site.
                            </div>
                        </div>
                    </label>
                </div>
            </FormSection>

            <FormSection
                title="Collection Cover"
                description="Choose and crop the image used anywhere this collection appears as a card."
            >
                <CollectionCoverEditor
                    :error="form.errors.cover_image || form.errors.cover_original"
                    @apply="applyCover"
                    @remove="removeCover"
                />
            </FormSection>

            <FormActions
                submit-label="Create Collection"
                processing-label="Creating..."
                :processing="form.processing"
                @submit="submit"
                @cancel="cancel"
            />
        </form>
    </div>
</template>

<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';

import FormActions from '@/Components/Forms/FormActions.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormGrid from '@/Components/Forms/FormGrid.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import DetailRow from '@/Components/Shared/DetailRow.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';

import type { AdminCollection } from '@/types/collection';

const props = defineProps<{
    collection: AdminCollection;
}>();

const form = useForm({
    name: props.collection.name,
    description: props.collection.description ?? '',
    sort_order: props.collection.sort_order,
    is_active: props.collection.is_active,
});

function submit() {
    form.put(`/admin/collections/${props.collection.id}`, {
        preserveScroll: true,
    });
}

function cancel() {
    router.visit('/admin/collections');
}
</script>

<template>
    <Head title="Edit Collection" />

    <div class="space-y-8 p-6">
        <PageHeader
            title="Edit Collection"
            description="Update an existing image collection."
        />

        <form
            class="space-y-8"
            @submit.prevent="submit"
        >
            <FormSection
                title="Collection Details"
                description="Update the collection name, description, display order, and active state."
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
                            placeholder="Enter collection name"
                        />
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
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Optional description"
                        />
                    </FormField>
                </div>

                <div class="mt-6 rounded-lg border bg-muted/20 p-4">
                    <label class="flex items-start gap-3">
                        <Checkbox
                            id="is_active"
                            :model-value="form.is_active"
                            @update:model-value="
                                form.is_active = $event === true
                            "
                        />

                        <div class="flex-1">
                            <div class="font-medium">
                                Active
                            </div>

                            <div class="text-sm text-muted-foreground">
                                Inactive collections can be hidden from public lists.
                            </div>
                        </div>

                        <StatusBadge
                            :status="form.is_active ? 'active' : 'inactive'"
                        />
                    </label>

                    <p
                        v-if="form.errors.is_active"
                        class="mt-2 text-sm text-destructive"
                    >
                        {{ form.errors.is_active }}
                    </p>
                </div>

                <div class="mt-6 rounded-lg border bg-muted/20 p-4">
                    <DetailRow
                        label="Slug"
                        :value="collection.slug"
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

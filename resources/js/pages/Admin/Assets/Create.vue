<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import FormActions from '@/Components/Forms/FormActions.vue';
import { Input } from '@/components/ui/input';
import AssetFileDropzone from '@/components/admin/assets/AssetFileDropzone.vue';
import AssetConfigurationBuilder from '@/components/admin/assets/AssetConfigurationBuilder.vue';
import type { AdminAssetConfigurationGroup, ConfigurationDisplayTypeOption, NamedOption, PendingAssetFile, SelectOption } from '@/types/adminAsset';
import type { ConfigurationTemplateSummary } from '@/types/configurationTemplate';

const props = defineProps<{
    collections: NamedOption[];
    assetTypes: SelectOption[];
    statuses: SelectOption[];
    fileRoles: SelectOption[];
    acceptedExtensions: string[];
    maxUploadKilobytes: number;
    configurationDisplayTypes: ConfigurationDisplayTypeOption[];
    configurationTemplates: ConfigurationTemplateSummary[];
}>();

const pendingFiles: PendingAssetFile[] = [];
const form = useForm({
    collection_id: '' as string | number,
    title: '',
    description: '',
    photographer: '',
    asset_type: 'image',
    status: 'draft',
    sort_order: 0,
    is_active: true,
    is_featured: false,
    is_ai_generated: false,
    allows_quantity: false,
    files: pendingFiles,
    primary_preview_index: null as number | null,
    poster_index: null as number | null,
    configurations: [] as AdminAssetConfigurationGroup[],
});

function submit() {
    form.transform((data) => ({
        ...data,
        collection_id: data.collection_id === '' ? null : data.collection_id,
        files: data.files.map((item) => item.file),
        file_roles: data.files.map((item) => item.role),
        file_downloadable: data.files.map((item) => item.downloadable ? 1 : 0),
    })).post('/admin/assets', { forceFormData: true });
}
</script>

<template>
    <Head title="Create Asset" />
    <div class="space-y-8 p-6">
        <PageHeader title="Create Asset" description="Create one marketplace asset with multiple associated files." />
        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
                <div class="space-y-6">
                    <FormSection title="Asset Details" description="Customer-facing information for the asset listing.">
                        <div class="grid gap-4 md:grid-cols-2">
                            <FormField label="Title" for-id="title" required :error="form.errors.title"><Input id="title" v-model="form.title" /></FormField>
                            <FormField label="Photographer / Creator" for-id="photographer" :error="form.errors.photographer"><Input id="photographer" v-model="form.photographer" /></FormField>
                            <FormField class="md:col-span-2" label="Description" for-id="description" :error="form.errors.description"><textarea id="description" v-model="form.description" rows="5" class="w-full rounded-md border bg-background px-3 py-2 text-sm" /></FormField>
                        </div>
                    </FormSection>
                    <FormSection title="Asset Files" description="Upload all deliverables belonging to this asset. Roles are suggested automatically and can be changed.">
                        <AssetFileDropzone v-model="form.files" :roles="fileRoles" :accepted-extensions="acceptedExtensions" :max-upload-kilobytes="maxUploadKilobytes" :disabled="form.processing" />
                        <p v-if="form.errors.files" class="mt-2 text-sm text-destructive">{{ form.errors.files }}</p>
                    </FormSection>
                    <FormSection title="Product Configuration" description="Optional customer choices such as size, color, resolution, or personalization.">
                        <AssetConfigurationBuilder v-model="form.configurations" :display-types="configurationDisplayTypes" :templates="configurationTemplates" />
                    </FormSection>
                </div>
                <div class="space-y-6">
                    <FormSection title="Classification" description="Control the asset type and workflow status.">
                        <div class="space-y-4">
                            <FormField label="Asset Type" for-id="asset_type"><select id="asset_type" v-model="form.asset_type" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option v-for="option in assetTypes" :key="option.value" :value="option.value">{{ option.label }}</option></select></FormField>
                            <FormField label="Status" for-id="status"><select id="status" v-model="form.status" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option v-for="option in statuses" :key="option.value" :value="option.value">{{ option.label }}</option></select></FormField>
                            <FormField label="Collection" for-id="collection_id"><select id="collection_id" v-model="form.collection_id" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option value="">No Collection</option><option v-for="collection in collections" :key="collection.id" :value="collection.id">{{ collection.name }}</option></select></FormField>
                            <FormField label="Sort Order" for-id="sort_order"><Input id="sort_order" v-model="form.sort_order" type="number" min="0" /></FormField>
                            <label class="flex gap-2 text-sm"><input v-model="form.is_active" type="checkbox" /> Active</label>
                            <label class="flex gap-2 text-sm"><input v-model="form.is_featured" type="checkbox" /> Featured</label>
                            <label class="flex gap-2 text-sm"><input v-model="form.is_ai_generated" type="checkbox" /> AI Generated</label>
                            <label class="flex items-start gap-2 text-sm"><input v-model="form.allows_quantity" type="checkbox" class="mt-0.5" /><span><span class="font-medium">Allow quantity selection</span><span class="mt-0.5 block text-xs text-muted-foreground">Enable this only when customers may order more than one unit.</span></span></label>
                        </div>
                    </FormSection>
                </div>
            </div>
            <FormActions submit-label="Create Asset" :processing="form.processing" @cancel="router.visit('/admin/assets')" />
        </form>
    </div>
</template>

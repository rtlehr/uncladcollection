<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminSectionNavigator from '@/components/admin/AdminSectionNavigator.vue';
import AssetConfigurationBuilder from '@/components/admin/assets/AssetConfigurationBuilder.vue';
import AssetFileDropzone from '@/components/admin/assets/AssetFileDropzone.vue';
import AssetMarketplaceImageEditor from '@/components/admin/assets/AssetMarketplaceImageEditor.vue';
import CreatableTagInput from '@/components/admin/tags/CreatableTagInput.vue';
import FormActions from '@/Components/Forms/FormActions.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import type { ImageEditData } from '@/components/media/ImageEditorDialog.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Input } from '@/components/ui/input';
import type { AdminAssetConfigurationGroup, ConfigurationDisplayTypeOption, NamedOption, PendingAssetFile, SelectOption } from '@/types/adminAsset';
import type { ConfigurationTemplateSummary } from '@/types/configurationTemplate';

defineProps<{
    collections: NamedOption[];
    assetTypes: SelectOption[];
    statuses: SelectOption[];
    fileRoles: SelectOption[];
    acceptedExtensions: string[];
    maxUploadKilobytes: number;
    fulfillmentTypes: SelectOption[];
    configurationDisplayTypes: ConfigurationDisplayTypeOption[];
    configurationTemplates: ConfigurationTemplateSummary[];
    imageTags: NamedOption[];
}>();

const pendingFiles: PendingAssetFile[] = [];
const form = useForm({
    collection_id: '' as string | number,
    title: '',
    description: '',
    tag_names: [] as string[],
    photographer: '',
    asset_type: 'image',
    status: 'draft',
    sort_order: 0,
    is_active: true,
    is_featured: false,
    is_ai_generated: false,
    allows_quantity: false,
    fulfillment_type: 'digital',
    collects_shipping_address: false,
    shipping_address_required: false,
    files: pendingFiles,
    primary_preview_index: null as number | null,
    poster_index: null as number | null,
    configurations: [] as AdminAssetConfigurationGroup[],
    marketplace_image: null as File | null,
    marketplace_edit_data: null as string | null,
    marketplace_source_index: null as number | null,
});

const hasInvalidFiles = computed(() =>
    form.files.some((item) => item.validationErrors.length > 0),
);

const uploadPercentage = computed(
    () => form.progress?.percentage ?? null,
);

const marketplacePreviewUrl = ref<string | null>(null);
const marketplaceEditData = ref<Partial<ImageEditData> | null>(null);
const marketplaceSourceKey = ref<string | null>(null);

const marketplaceSources = computed(() =>
    form.files
        .map((item, index) => ({ item, index }))
        .filter(({ item }) => item.metadata.kind === 'image' && item.previewUrl)
        .map(({ item, index }) => ({
            key: String(index),
            label: item.file.name,
            source: item.file,
            sourceAssetFileId: null,
        })),
);

const marketplaceFormats = computed(() =>
    form.files
        .map((item) => item.metadata.extension.toUpperCase())
        .filter((value, index, values) => values.indexOf(value) === index),
);

function applyMarketplaceImage(payload: {
    file: File;
    edit: ImageEditData;
    previewUrl: string;
    sourceKey: string;
}): void {
    if (marketplacePreviewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(marketplacePreviewUrl.value);
    }

    marketplacePreviewUrl.value = payload.previewUrl;
    marketplaceEditData.value = payload.edit;
    marketplaceSourceKey.value = payload.sourceKey;
    form.marketplace_image = payload.file;
    form.marketplace_edit_data = JSON.stringify(payload.edit);
    form.marketplace_source_index = Number(payload.sourceKey);
}

const adminSections = [
    { id: 'asset-details', title: 'Asset Details', description: 'Title, creator, and description.', errorKeys: ['title', 'photographer', 'description'] },
    { id: 'asset-files', title: 'Asset Files', description: 'Upload and classify deliverables.', errorKeys: ['files', 'file_roles', 'file_downloadable'] },
    { id: 'asset-presentation', title: 'Presentation', description: 'Marketplace card image.', errorKeys: ['marketplace_image'] },
    { id: 'asset-configuration', title: 'Configuration', description: 'Customer-selectable options.', errorKeys: ['configurations'] },
    { id: 'asset-classification', title: 'Publishing', description: 'Type, status, collection, and fulfillment.', errorKeys: ['asset_type', 'status', 'collection_id', 'fulfillment_type'] },
];

function submit() {
    if (hasInvalidFiles.value) {
        return;
    }

    form.transform((data) => ({
        ...data,
        collection_id: data.collection_id === '' ? null : data.collection_id,
        files: data.files.map((item) => item.file),
        file_roles: data.files.map((item) => item.role),
        file_downloadable: data.files.map((item) => item.downloadable ? 1 : 0),
    })).post('/admin/assets', {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Create Asset" />
    <div class="space-y-8 p-6">
        <PageHeader title="Create Asset" description="Create one marketplace asset with multiple associated files." />
        <AdminSectionNavigator :sections="adminSections" :errors="form.errors" label="Asset sections" storage-key="admin.assets.create.workspace" v-slot="{ activeSection }">
            <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-6">
                <div class="space-y-6">
                    <FormSection v-show="activeSection === 'asset-details'"
                        id="asset-details" class="scroll-mt-24" title="Asset Details" description="Customer-facing information for the asset listing.">
                        <div class="grid gap-4 md:grid-cols-2">
                            <FormField label="Title" for-id="title" required :error="form.errors.title"><Input id="title" v-model="form.title" /></FormField>
                            <FormField label="Photographer / Creator" for-id="photographer" :error="form.errors.photographer"><Input id="photographer" v-model="form.photographer" /></FormField>
                            <FormField class="md:col-span-2" label="Description" for-id="description" :error="form.errors.description"><textarea id="description" v-model="form.description" rows="5" class="w-full rounded-md border bg-background px-3 py-2 text-sm" /></FormField>
                            <FormField class="md:col-span-2" label="Keywords" for-id="keywords" :error="form.errors.tag_names"><CreatableTagInput v-model="form.tag_names" :options="imageTags" /></FormField>
                        </div>
                    </FormSection>
                    <FormSection v-show="activeSection === 'asset-files'"
                        id="asset-files" class="scroll-mt-24" title="Asset Files" description="Upload all deliverables belonging to this asset. Roles are suggested automatically and can be changed.">
                        <AssetFileDropzone
                            v-model="form.files"
                            v-model:primary-preview-index="form.primary_preview_index"
                            v-model:poster-index="form.poster_index"
                            :roles="fileRoles"
                            :accepted-extensions="acceptedExtensions"
                            :max-upload-kilobytes="maxUploadKilobytes"
                            :disabled="form.processing"
                            allow-primary-selection
                            allow-poster-selection
                        />
                        <p
                            v-if="form.errors.files"
                            class="mt-2 text-sm text-destructive"
                        >
                            {{ form.errors.files }}
                        </p>

                        <div
                            v-if="form.processing && uploadPercentage !== null"
                            class="mt-4 rounded-xl border bg-muted/20 p-4"
                        >
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium">Uploading asset files</span>
                                <span class="text-muted-foreground">
                                    {{ uploadPercentage }}%
                                </span>
                            </div>

                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full bg-primary transition-all"
                                    :style="{ width: `${uploadPercentage}%` }"
                                />
                            </div>

                            <p class="mt-2 text-xs text-muted-foreground">
                                Keep this page open until the upload completes.
                            </p>
                        </div>
                    </FormSection>
                    <FormSection
                        v-show="activeSection === 'asset-presentation'"
                        id="asset-presentation"
                        class="scroll-mt-24"
                        title="Asset Presentation"
                        description="Create the dedicated 16:9 crop used on marketplace cards."
                    >
                        <AssetMarketplaceImageEditor
                            v-model:source-key="marketplaceSourceKey"
                            :sources="marketplaceSources"
                            :preview-url="marketplacePreviewUrl"
                            :edit-data="marketplaceEditData"
                            :title="form.title"
                            :creator="form.photographer"
                            :asset-type-label="assetTypes.find((item) => item.value === form.asset_type)?.label ?? 'Asset'"
                            :formats="marketplaceFormats"
                            :disabled="form.processing"
                            @apply="applyMarketplaceImage"
                        />

                        <p
                            v-if="form.errors.marketplace_image"
                            class="mt-2 text-sm text-destructive"
                        >
                            {{ form.errors.marketplace_image }}
                        </p>
                    </FormSection>

                    <FormSection v-show="activeSection === 'asset-configuration'"
                        id="asset-configuration" class="scroll-mt-24" title="Product Configuration" description="Optional customer choices such as size, color, resolution, or personalization.">
                        <AssetConfigurationBuilder v-model="form.configurations" :display-types="configurationDisplayTypes" :templates="configurationTemplates" />
                    </FormSection>
                </div>
                <div class="space-y-6">
                    <FormSection v-show="activeSection === 'asset-classification'"
                        id="asset-classification" class="scroll-mt-24" title="Classification" description="Control the asset type and workflow status.">
                        <div class="space-y-4">
                            <FormField label="Asset Type" for-id="asset_type"><select id="asset_type" v-model="form.asset_type" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option v-for="option in assetTypes" :key="option.value" :value="option.value">{{ option.label }}</option></select></FormField>
                            <FormField label="Status" for-id="status"><select id="status" v-model="form.status" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option v-for="option in statuses" :key="option.value" :value="option.value">{{ option.label }}</option></select></FormField>
                            <FormField label="Collection" for-id="collection_id"><select id="collection_id" v-model="form.collection_id" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option value="">No Collection</option><option v-for="collection in collections" :key="collection.id" :value="collection.id">{{ collection.name }}</option></select></FormField>
                            <FormField label="Sort Order" for-id="sort_order"><Input id="sort_order" v-model="form.sort_order" type="number" min="0" /></FormField>
                            <label class="flex gap-2 text-sm"><input v-model="form.is_active" type="checkbox" /> Active</label>
                            <label class="flex gap-2 text-sm"><input v-model="form.is_featured" type="checkbox" /> Featured</label>
                            <label class="flex gap-2 text-sm"><input v-model="form.is_ai_generated" type="checkbox" /> AI Generated</label>
                            <label class="flex items-start gap-2 text-sm"><input v-model="form.allows_quantity" type="checkbox" class="mt-0.5" /><span><span class="font-medium">Allow quantity selection</span><span class="mt-0.5 block text-xs text-muted-foreground">Enable this only when customers may order more than one unit.</span></span></label>
                        <FormField label="Fulfillment" for-id="fulfillment_type"><select id="fulfillment_type" v-model="form.fulfillment_type" class="h-10 w-full rounded-md border bg-background px-3 text-sm"><option v-for="option in fulfillmentTypes" :key="option.value" :value="option.value">{{ option.label }}</option></select></FormField>
                        <label class="flex items-start gap-2 text-sm"><input v-model="form.collects_shipping_address" type="checkbox" class="mt-0.5" /><span><span class="font-medium">Collect shipping address</span><span class="mt-0.5 block text-xs text-muted-foreground">Show delivery-address fields on the public asset page and preserve the address with the cart and order.</span></span></label>
                        <label v-if="form.collects_shipping_address" class="ml-6 flex items-start gap-2 text-sm"><input v-model="form.shipping_address_required" type="checkbox" class="mt-0.5" /><span><span class="font-medium">Shipping address is mandatory</span><span class="mt-0.5 block text-xs text-muted-foreground">Keep Add to Cart disabled until the customer completes the required address fields.</span></span></label>
                        </div>
                    </FormSection>
                </div>
            </div>
            <FormActions submit-label="Create Asset" :processing="form.processing" :disabled="hasInvalidFiles" @cancel="router.visit('/admin/assets')" />
            </form>
        </AdminSectionNavigator>
    </div>
</template>

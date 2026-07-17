<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminSectionNavigator from '@/components/admin/AdminSectionNavigator.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import FormActions from '@/Components/Forms/FormActions.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AssetFileDropzone from '@/components/admin/assets/AssetFileDropzone.vue';
import { useDeleteConfirmation } from '@/composables/useDeleteConfirmation';
import AssetOfferingBuilder from '@/components/admin/assets/AssetOfferingBuilder.vue';
import AssetOfferingMatrix from '@/components/admin/assets/AssetOfferingMatrix.vue';
import AssetFilePreviewGallery from '@/components/unclad/assets/AssetFilePreviewGallery.vue';
import AssetHealthCard from '@/components/admin/assets/AssetHealthCard.vue';
import AssetFileWorkspace from '@/components/admin/assets/AssetFileWorkspace.vue';
import AssetConfigurationBuilder from '@/components/admin/assets/AssetConfigurationBuilder.vue';
import AssetMarketplaceImageEditor from '@/components/admin/assets/AssetMarketplaceImageEditor.vue';
import AssetFileRelationshipManager from '@/components/admin/assets/AssetFileRelationshipManager.vue';
import type { ImageEditData } from '@/components/media/ImageEditorDialog.vue';
import type {
    AdminAsset,
    AdminAssetFile,
    AdminAssetFileRelationship,
    AssetFileRelationshipTypeOption,
    NamedOption,
    PendingAssetFile,
    SelectOption,
    AdminAssetOffering,
    LicenseTypeOption,
    AdminAssetConfigurationGroup,
    ConfigurationDisplayTypeOption,
} from '@/types/adminAsset';
import type { ConfigurationTemplateSummary } from '@/types/configurationTemplate';

const props = defineProps<{
    assetRecord: AdminAsset;
    collections: NamedOption[];
    assetTypes: SelectOption[];
    statuses: SelectOption[];
    fileRoles: SelectOption[];
    acceptedExtensions: string[];
    maxUploadKilobytes: number;
    licenseTypes: LicenseTypeOption[];
    fulfillmentTypes: SelectOption[];
    configurationDisplayTypes: ConfigurationDisplayTypeOption[];
    configurationTemplates: ConfigurationTemplateSummary[];
    relationshipTypes: AssetFileRelationshipTypeOption[];
}>();

const form = useForm({
    collection_id: props.assetRecord.collection_id ?? '' as string | number,
    title: props.assetRecord.title,
    description: props.assetRecord.description ?? '',
    photographer: props.assetRecord.photographer ?? '',
    asset_type: props.assetRecord.asset_type,
    status: props.assetRecord.status,
    sort_order: props.assetRecord.sort_order,
    is_active: props.assetRecord.is_active,
    is_featured: props.assetRecord.is_featured,
    is_ai_generated: props.assetRecord.is_ai_generated,
    allows_quantity: props.assetRecord.allows_quantity,
    fulfillment_type: props.assetRecord.fulfillment_type,
    collects_shipping_address: props.assetRecord.collects_shipping_address,
    shipping_address_required: props.assetRecord.shipping_address_required,
});

const pendingFiles = ref<PendingAssetFile[]>([]);
const uploadForm = useForm({ files: [] as File[], file_roles: [] as string[], file_downloadable: [] as number[] });
const replacingId = ref<number | null>(null);
const offeringForm = useForm({ offerings: (props.assetRecord.offerings ?? []) as AdminAssetOffering[] });
const configurationForm = useForm({ configurations: (props.assetRecord.configurations ?? []) as AdminAssetConfigurationGroup[] });
const relationshipForm = useForm({
    relationships: (
        props.assetRecord.file_relationships ?? []
    ) as AdminAssetFileRelationship[],
});
const deletion = useDeleteConfirmation<AdminAssetFile>();

const marketplacePreviewUrl = ref<string | null>(
    props.assetRecord.marketplace_image_url,
);
const marketplaceEditData = ref<Partial<ImageEditData> | null>(
    props.assetRecord.marketplace_image_edit_data as Partial<ImageEditData> | null,
);
const marketplaceSourceKey = ref<string | null>(
    props.assetRecord.marketplace_source_asset_file_id
        ? String(props.assetRecord.marketplace_source_asset_file_id)
        : null,
);
const presentationForm = useForm({
    marketplace_image: null as File | null,
    marketplace_edit_data: null as string | null,
    marketplace_source_asset_file_id:
        props.assetRecord.marketplace_source_asset_file_id as number | null,
    remove_marketplace_image: false,
});

const marketplaceSources = computed(() =>
    (props.assetRecord.files ?? [])
        .filter(
            (file) =>
                file.can_preview &&
                ['image', 'vector'].includes(file.preview_kind) &&
                Boolean(file.preview_url),
        )
        .map((file) => ({
            key: String(file.id),
            label: `${file.original_filename} (${file.extension.toUpperCase()})`,
            source: file.preview_url as string,
            sourceAssetFileId: file.id,
        })),
);

const marketplaceFormats = computed(() =>
    (props.assetRecord.files ?? [])
        .map((file) => file.extension.toUpperCase())
        .filter((value, index, values) => values.indexOf(value) === index),
);

function applyMarketplaceImage(payload: {
    file: File;
    edit: ImageEditData;
    previewUrl: string;
    sourceAssetFileId: number | null;
}): void {
    if (marketplacePreviewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(marketplacePreviewUrl.value);
    }

    marketplacePreviewUrl.value = payload.previewUrl;
    marketplaceEditData.value = payload.edit;
    presentationForm.marketplace_image = payload.file;
    presentationForm.marketplace_edit_data = JSON.stringify(payload.edit);
    presentationForm.marketplace_source_asset_file_id =
        payload.sourceAssetFileId;
    presentationForm.remove_marketplace_image = false;
}

function saveMarketplaceImage(): void {
    presentationForm.post(
        `/admin/assets/${props.assetRecord.id}/presentation`,
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                presentationForm.marketplace_image = null;
                presentationForm.remove_marketplace_image = false;
            },
        },
    );
}

function clearMarketplaceImage(): void {
    presentationForm.marketplace_image = null;
    presentationForm.marketplace_edit_data = null;
    presentationForm.remove_marketplace_image = true;

    presentationForm.post(
        `/admin/assets/${props.assetRecord.id}/presentation`,
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                marketplacePreviewUrl.value = null;
                marketplaceEditData.value = null;
                presentationForm.remove_marketplace_image = false;
            },
        },
    );
}

const canViewPublicPage = computed(
    () => props.assetRecord.status === 'published' && props.assetRecord.is_active,
);

function viewPublicPage(): void {
    if (!canViewPublicPage.value) {
        return;
    }

    window.open(
        `/assets/${encodeURIComponent(props.assetRecord.slug)}`,
        '_blank',
        'noopener,noreferrer',
    );
}

const adminSections = [
    { id: 'asset-overview', title: 'Overview', description: 'Asset health and core details.', errorKeys: ['title', 'photographer', 'description', 'asset_type', 'status'] },
    { id: 'asset-presentation', title: 'Presentation', description: 'Marketplace image and public preview.', errorKeys: ['marketplace_image'] },
    { id: 'asset-files', title: 'Files', description: 'Preview, reorder, replace, and upload files.', errorKeys: ['files'] },
    { id: 'asset-relationships', title: 'Relationships', description: 'Connect source and derived files.', errorKeys: ['relationships'] },
    { id: 'asset-configuration', title: 'Configuration', description: 'Customer-selectable options.', errorKeys: ['configurations'] },
    { id: 'asset-offerings', title: 'License Offerings', description: 'Pricing, licenses, and included files.', errorKeys: ['offerings'] },
];

function updateAsset() {
    form.transform((data) => ({ ...data, collection_id: data.collection_id === '' ? null : data.collection_id }))
        .put(`/admin/assets/${props.assetRecord.id}`, { preserveScroll: true });
}

const pendingFilesAreValid = computed(
    () =>
        pendingFiles.value.length > 0 &&
        pendingFiles.value.every(
            (item) => item.validationErrors.length === 0,
        ),
);

const uploadPercentage = computed(
    () => uploadForm.progress?.percentage ?? null,
);

function uploadFiles() {
    if (!pendingFilesAreValid.value) {
        return;
    }
    uploadForm.files = pendingFiles.value.map((item) => item.file);
    uploadForm.file_roles = pendingFiles.value.map((item) => item.role);
    uploadForm.file_downloadable = pendingFiles.value.map((item) => item.downloadable ? 1 : 0);
    uploadForm.post(`/admin/assets/${props.assetRecord.id}/files`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { pendingFiles.value = []; uploadForm.reset(); },
    });
}

function saveFile(file: AdminAssetFile) {
    router.patch(`/admin/assets/${props.assetRecord.id}/files/${file.id}`, {
        role: file.role,
        is_downloadable: file.is_downloadable,
        is_active: file.is_active,
        primary_preview: file.is_primary_preview,
        poster: file.is_poster,
    }, { preserveScroll: true });
}

function replaceFile(file: AdminAssetFile, event: Event) {
    const replacement = (event.target as HTMLInputElement).files?.[0];
    if (!replacement) return;
    replacingId.value = file.id;
    router.post(`/admin/assets/${props.assetRecord.id}/files/${file.id}/replace`, { file: replacement }, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => replacingId.value = null,
    });
}

function removeFile(file: AdminAssetFile): void {
    deletion.requestDelete(file);
}

function confirmRemoveFile(): void {
    deletion.runDelete((file, finish) => {
        router.delete(`/admin/assets/${props.assetRecord.id}/files/${file.id}`, {
            preserveScroll: true,
            onFinish: finish,
        });
    });
}

function saveConfigurations(): void {
    configurationForm.put(`/admin/assets/${props.assetRecord.id}/configurations`, { preserveScroll: true });
}

function saveRelationships(): void {
    relationshipForm.put(
        `/admin/assets/${props.assetRecord.id}/relationships`,
        {
            preserveScroll: true,
        },
    );
}

function saveOfferings(): void {
    offeringForm.put(`/admin/assets/${props.assetRecord.id}/offerings`, { preserveScroll: true });
}

function reorderFiles(files: AdminAssetFile[]): void {
    router.put(`/admin/assets/${props.assetRecord.id}/files/order`, {
        files: files.map((file, position) => ({
            id: file.id,
            sort_order: (position + 1) * 10,
        })),
    }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Edit ${assetRecord.title}`" />
    <div class="space-y-8 p-6">
        <PageHeader
            :title="`Edit ${assetRecord.title}`"
            description="Manage asset details, associated files, previews, and revisions."
        >
            <div class="flex flex-wrap items-center gap-3">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="!canViewPublicPage"
                    :title="canViewPublicPage ? 'Open the public asset page in a new tab' : 'Publish and activate this asset before viewing its public page'"
                    @click="viewPublicPage"
                >
                    View Public Page
                </Button>

                <span
                    v-if="!canViewPublicPage"
                    class="text-sm text-muted-foreground"
                >
                    Publish and activate this asset to enable the public page.
                </span>
            </div>
        </PageHeader>

        <AdminSectionNavigator :sections="adminSections" :errors="{ ...form.errors, ...presentationForm.errors, ...relationshipForm.errors, ...configurationForm.errors, ...offeringForm.errors, ...uploadForm.errors }" label="Asset sections" storage-key="admin.assets.edit.workspace" v-slot="{ activeSection }">
        <div v-show="activeSection === 'asset-overview'" id="asset-overview" class="space-y-6">
            <AssetHealthCard :health="assetRecord.health" />

        <form class="space-y-6" @submit.prevent="updateAsset">
            <div class="grid gap-6 xl:grid-cols-2">
                <FormSection title="Asset Details" description="Customer-facing information and classification.">
                    <div class="grid gap-4 md:grid-cols-2">
                        <FormField label="Title" for-id="title" :error="form.errors.title"><Input id="title" v-model="form.title" /></FormField>
                        <FormField label="Creator" for-id="photographer"><Input id="photographer" v-model="form.photographer" /></FormField>
                        <FormField class="md:col-span-2" label="Description" for-id="description"><textarea id="description" v-model="form.description" rows="5" class="w-full rounded-md border bg-background px-3 py-2 text-sm" /></FormField>
                    </div>
                </FormSection>
                <FormSection title="Publishing" description="Asset type, workflow, and public availability.">
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
            <FormActions submit-label="Save Asset" :processing="form.processing" @cancel="router.visit('/admin/assets')" />
        </form>
        </div>

        <FormSection
            v-show="activeSection === 'asset-presentation'"
            id="asset-presentation"
            class="scroll-mt-24"
            title="Asset Presentation"
            description="Control the dedicated marketplace-card image independently from the full asset preview."
        >
            <form class="space-y-5" @submit.prevent="saveMarketplaceImage">
                <AssetMarketplaceImageEditor
                    v-model:source-key="marketplaceSourceKey"
                    :sources="marketplaceSources"
                    :preview-url="marketplacePreviewUrl"
                    :edit-data="marketplaceEditData"
                    :title="assetRecord.title"
                    :creator="assetRecord.photographer"
                    :asset-type-label="assetTypes.find((item) => item.value === assetRecord.asset_type)?.label ?? 'Asset'"
                    :formats="marketplaceFormats"
                    :disabled="presentationForm.processing"
                    allow-clear
                    @apply="applyMarketplaceImage"
                    @clear="clearMarketplaceImage"
                />

                <div class="flex justify-end border-t pt-4">
                    <Button
                        type="submit"
                        :disabled="
                            presentationForm.processing ||
                            !presentationForm.marketplace_image
                        "
                    >
                        {{
                            presentationForm.processing
                                ? 'Saving…'
                                : 'Save Marketplace Image'
                        }}
                    </Button>
                </div>

                <p
                    v-if="presentationForm.errors.marketplace_image"
                    class="text-sm text-destructive"
                >
                    {{ presentationForm.errors.marketplace_image }}
                </p>
            </form>
        </FormSection>

        <div v-show="activeSection === 'asset-files'" id="asset-files" class="space-y-6">
        <FormSection title="Preview Gallery" description="Review browser-safe asset files using the same presentation framework as the public page.">
            <AssetFilePreviewGallery
                :files="assetRecord.files ?? []"
                :asset-title="assetRecord.title"
                :initial-file-id="assetRecord.primary_preview_file_id"
                compact
            />
        </FormSection>

        <FormSection title="File Workspace" description="Drag files to reorder them, update presentation settings inline, and manage revisions from one workspace.">
            <AssetFileWorkspace
                :files="assetRecord.files ?? []"
                :roles="fileRoles"
                :replacing-id="replacingId"
                :accepted-extensions="acceptedExtensions"
                @save="saveFile"
                @replace="replaceFile"
                @remove="removeFile"
                @reorder="reorderFiles"
            />
        </FormSection>


        </div>

        <ConfirmActionDialog
            v-model:open="deletion.open.value"
            title="Remove asset file?"
            :description="
                deletion.selected.value
                    ? `Remove '${deletion.selected.value.original_filename}' from this asset? The stored revision will be retained for historical licenses and audit purposes.`
                    : 'The stored revision will be retained for historical licenses and audit purposes.'
            "
            confirm-label="Remove File"
            processing-label="Removing..."
            destructive
            :loading="deletion.processing.value"
            @confirm="confirmRemoveFile"
            @cancel="deletion.cancelDelete"
        />



        <FormSection
            v-show="activeSection === 'asset-relationships'"
            id="asset-relationships"
            class="scroll-mt-24"
            title="File Relationships"
            description="Connect previews, source files, videos, archives, and related deliverables."
        >
            <form class="space-y-5" @submit.prevent="saveRelationships">
                <AssetFileRelationshipManager
                    v-model="relationshipForm.relationships"
                    :files="assetRecord.files ?? []"
                    :relationship-types="relationshipTypes"
                    :processing="relationshipForm.processing"
                />

                <div
                    v-if="Object.keys(relationshipForm.errors).length"
                    class="rounded-xl border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
                >
                    <p class="font-medium">
                        Some relationships need attention.
                    </p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li
                            v-for="(message, key) in relationshipForm.errors"
                            :key="key"
                        >
                            {{ message }}
                        </li>
                    </ul>
                </div>

                <div class="flex justify-end border-t pt-4">
                    <Button
                        type="submit"
                        :disabled="relationshipForm.processing"
                    >
                        {{
                            relationshipForm.processing
                                ? 'Saving…'
                                : 'Save Relationships'
                        }}
                    </Button>
                </div>
            </form>
        </FormSection>

        <FormSection v-show="activeSection === 'asset-configuration'" id="asset-configuration" class="scroll-mt-24" title="Product Configuration" description="Optional customer choices such as size, color, resolution, language, or personalization.">
            <form class="space-y-6" @submit.prevent="saveConfigurations">
                <AssetConfigurationBuilder v-model="configurationForm.configurations" :display-types="configurationDisplayTypes" :templates="configurationTemplates" />
                <div class="flex justify-end border-t pt-4"><Button type="submit" :disabled="configurationForm.processing">{{ configurationForm.processing ? 'Saving…' : 'Save Configuration' }}</Button></div>
            </form>
        </FormSection>

        <FormSection v-show="activeSection === 'asset-offerings'" id="asset-offerings" class="scroll-mt-24" title="License Offerings" description="Build customer packages, compare coverage, and review exactly what each license delivers.">
            <form class="space-y-6" @submit.prevent="saveOfferings">
                <AssetOfferingMatrix
                    :files="assetRecord.files ?? []"
                    :offerings="offeringForm.offerings"
                />

                <AssetOfferingBuilder
                    v-model="offeringForm.offerings"
                    :files="assetRecord.files ?? []"
                    :license-types="licenseTypes"
                />

                <div class="sticky bottom-0 z-10 flex justify-end border-t bg-background/95 py-4 backdrop-blur">
                    <Button type="submit" :disabled="offeringForm.processing">
                        {{ offeringForm.processing ? 'Saving…' : 'Save Offerings' }}
                    </Button>
                </div>
            </form>
        </FormSection>

        <FormSection v-show="activeSection === 'asset-files'" id="asset-add-files" class="scroll-mt-24" title="Add Files" description="Upload additional associated files to this existing asset.">
            <AssetFileDropzone
                v-model="pendingFiles"
                :roles="fileRoles"
                :accepted-extensions="acceptedExtensions"
                :max-upload-kilobytes="maxUploadKilobytes"
                :disabled="uploadForm.processing"
            />

            <div
                v-if="uploadForm.processing && uploadPercentage !== null"
                class="rounded-xl border bg-muted/20 p-4"
            >
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium">Uploading files</span>
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
            </div>

            <div
                v-if="Object.keys(uploadForm.errors).length"
                class="rounded-xl border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
            >
                <p class="font-medium">Some files could not be uploaded.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li
                        v-for="(message, key) in uploadForm.errors"
                        :key="key"
                    >
                        {{ message }}
                    </li>
                </ul>
            </div>
            <div class="mt-4 flex justify-end"><Button type="button" :disabled="!pendingFilesAreValid || uploadForm.processing" @click="uploadFiles">Upload {{ pendingFiles.length || '' }} File{{ pendingFiles.length === 1 ? '' : 's' }}</Button></div>
        </FormSection>
        </AdminSectionNavigator>
    </div>
</template>

<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
import type { AdminAsset, AdminAssetFile, NamedOption, PendingAssetFile, SelectOption, AdminAssetOffering, LicenseTypeOption } from '@/types/adminAsset';

const props = defineProps<{
    assetRecord: AdminAsset;
    collections: NamedOption[];
    assetTypes: SelectOption[];
    statuses: SelectOption[];
    fileRoles: SelectOption[];
    acceptedExtensions: string[];
    maxUploadKilobytes: number;
    licenseTypes: LicenseTypeOption[];
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
});

const pendingFiles = ref<PendingAssetFile[]>([]);
const uploadForm = useForm({ files: [] as File[], file_roles: [] as string[], file_downloadable: [] as number[] });
const replacingId = ref<number | null>(null);
const offeringForm = useForm({ offerings: (props.assetRecord.offerings ?? []) as AdminAssetOffering[] });
const deletion = useDeleteConfirmation<AdminAssetFile>();

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

function updateAsset() {
    form.transform((data) => ({ ...data, collection_id: data.collection_id === '' ? null : data.collection_id }))
        .put(`/admin/assets/${props.assetRecord.id}`, { preserveScroll: true });
}

function uploadFiles() {
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

function saveOfferings(): void {
    offeringForm.put(`/admin/assets/${props.assetRecord.id}/offerings`, { preserveScroll: true });
}

function moveFile(index: number, offset: number) {
    const files = [...(props.assetRecord.files ?? [])];
    const target = index + offset;
    if (target < 0 || target >= files.length) return;
    [files[index], files[target]] = [files[target], files[index]];
    router.put(`/admin/assets/${props.assetRecord.id}/files/order`, {
        files: files.map((file, position) => ({ id: file.id, sort_order: (position + 1) * 10 })),
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

        <form class="space-y-6" @submit.prevent="updateAsset">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
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
                    </div>
                </FormSection>
            </div>
            <FormActions submit-label="Save Asset" :processing="form.processing" @cancel="router.visit('/admin/assets')" />
        </form>

        <FormSection title="Current Files" description="Change roles, preview assignments, downloadability, order, or replace a revision.">
            <div class="space-y-3">
                <div v-for="(file, index) in assetRecord.files" :key="file.id" class="grid gap-3 rounded-lg border p-4 xl:grid-cols-[40px_minmax(0,1fr)_180px_1fr_auto] xl:items-center">
                    <div class="flex flex-col text-xs"><button type="button" :disabled="index === 0" @click="moveFile(index, -1)">▲</button><button type="button" :disabled="index === (assetRecord.files?.length ?? 0) - 1" @click="moveFile(index, 1)">▼</button></div>
                    <div class="min-w-0">
                        <div class="truncate font-medium">{{ file.original_filename }}</div>
                        <div class="text-xs text-muted-foreground">{{ file.media_type }} · {{ file.extension.toUpperCase() }} · {{ file.size_bytes ? (file.size_bytes / 1024 / 1024).toFixed(2) + ' MB' : 'Unknown size' }}</div>
                        <div class="mt-1 flex gap-2"><StatusBadge :status="file.processing_status" /><StatusBadge :status="file.virus_scan_status" /></div>
                    </div>
                    <select v-model="file.role" class="h-10 rounded-md border bg-background px-3 text-sm"><option v-for="role in fileRoles" :key="role.value" :value="role.value">{{ role.label }}</option></select>
                    <div class="flex flex-wrap gap-3 text-sm">
                        <label class="flex gap-2"><input v-model="file.is_downloadable" type="checkbox" /> Downloadable</label>
                        <label class="flex gap-2"><input v-model="file.is_primary_preview" type="checkbox" /> Primary preview</label>
                        <label class="flex gap-2"><input v-model="file.is_poster" type="checkbox" /> Poster</label>
                    </div>
                    <div class="flex flex-wrap justify-end gap-2">
                        <Button type="button" size="sm" variant="outline" @click="saveFile(file)">Save</Button>
                        <label class="inline-flex h-9 cursor-pointer items-center rounded-md border px-3 text-sm">{{ replacingId === file.id ? 'Replacing…' : 'Replace' }}<input class="hidden" type="file" :accept="acceptedExtensions.map(ext => '.' + ext).join(',')" @change="replaceFile(file, $event)" /></label>
                        <Button type="button" size="sm" variant="destructive" @click="removeFile(file)">Remove</Button>
                    </div>
                </div>
                <p v-if="!assetRecord.files?.length" class="rounded-lg border border-dashed p-8 text-center text-muted-foreground">No active files.</p>
            </div>
        </FormSection>


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

        <FormSection title="License Offerings" description="Build customer packages, compare coverage, and review exactly what each license delivers.">
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

        <FormSection title="Add Files" description="Upload additional associated files to this existing asset.">
            <AssetFileDropzone v-model="pendingFiles" :roles="fileRoles" :accepted-extensions="acceptedExtensions" :max-upload-kilobytes="maxUploadKilobytes" :disabled="uploadForm.processing" />
            <div class="mt-4 flex justify-end"><Button type="button" :disabled="!pendingFiles.length || uploadForm.processing" @click="uploadFiles">Upload {{ pendingFiles.length || '' }} File{{ pendingFiles.length === 1 ? '' : 's' }}</Button></div>
        </FormSection>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import { Button } from '@/components/ui/button';
import type { AdminAssetFile, AdminAssetOffering, LicenseTypeOption } from '@/types/adminAsset';

const model = defineModel<AdminAssetOffering[]>({ required: true });
const props = defineProps<{
    files: AdminAssetFile[];
    licenseTypes: LicenseTypeOption[];
}>();

const removeIndex = ref<number | null>(null);

const canAdd = computed(() => model.value.length < props.licenseTypes.length);
const activeDownloadableFiles = computed(() =>
    props.files.filter((file) => file.is_active && file.is_downloadable),
);

function addOffering(): void {
    const used = new Set(model.value.map((item) => item.license_type_id));
    const license = props.licenseTypes.find((item) => !used.has(item.id));

    if (!license) {
        return;
    }

    model.value.push({
        id: null,
        license_type_id: license.id,
        name: license.name,
        description: license.description ?? '',
        price_cents: license.price_cents,
        currency: license.currency,
        download_limit: license.download_limit,
        expires_after_days: license.expires_after_days,
        include_all_active_files: false,
        is_active: true,
        file_ids: [],
    });
}

function requestRemoveOffering(index: number): void {
    removeIndex.value = index;
}

function confirmRemoveOffering(): void {
    if (removeIndex.value === null) {
        return;
    }

    model.value.splice(removeIndex.value, 1);
    removeIndex.value = null;
}

function selectedFiles(offering: AdminAssetOffering): AdminAssetFile[] {
    return offering.include_all_active_files
        ? activeDownloadableFiles.value
        : props.files.filter((file) => offering.file_ids.includes(file.id));
}

function totalBytes(offering: AdminAssetOffering): number {
    return selectedFiles(offering).reduce((sum, file) => sum + (file.size_bytes ?? 0), 0);
}

function formatBytes(bytes: number): string {
    if (bytes <= 0) {
        return '0 MB';
    }

    if (bytes >= 1024 ** 3) {
        return `${(bytes / 1024 ** 3).toFixed(2)} GB`;
    }

    return `${(bytes / 1024 ** 2).toFixed(2)} MB`;
}

function formatFileSize(file: AdminAssetFile): string {
    return formatBytes(file.size_bytes ?? 0);
}

function formatRole(role: string): string {
    return role.replaceAll('_', ' ');
}

function priceValue(offering: AdminAssetOffering): string {
    return (offering.price_cents / 100).toFixed(2);
}

function updatePrice(offering: AdminAssetOffering, event: Event): void {
    const value = Number((event.target as HTMLInputElement).value);
    offering.price_cents = Number.isFinite(value) ? Math.max(0, Math.round(value * 100)) : 0;
}

function packageStatus(offering: AdminAssetOffering): string {
    if (!offering.is_active) {
        return 'Inactive';
    }

    return selectedFiles(offering).length > 0 ? 'Ready' : 'Needs files';
}
</script>

<template>
    <div class="space-y-5">
        <div
            v-for="(offering, index) in model"
            :key="offering.id ?? `new-${index}`"
            class="overflow-hidden rounded-xl border bg-background"
        >
            <div class="flex flex-col gap-4 border-b bg-muted/25 p-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-base font-semibold">{{ offering.name || 'Untitled Offering' }}</h3>
                        <span
                            class="rounded-full border px-2 py-0.5 text-xs"
                            :class="offering.is_active ? 'bg-background' : 'text-muted-foreground'"
                        >
                            {{ packageStatus(offering) }}
                        </span>
                        <span
                            v-if="offering.include_all_active_files"
                            class="rounded-full border bg-primary/5 px-2 py-0.5 text-xs text-primary"
                        >
                            Auto includes active files
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Configure customer-facing terms and the files delivered with this license.
                    </p>
                </div>

                <Button
                    type="button"
                    variant="destructive"
                    size="sm"
                    @click="requestRemoveOffering(index)"
                >
                    Remove Offering
                </Button>
            </div>

            <div class="grid gap-6 p-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
                <div class="space-y-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-1.5 text-sm">
                            <span class="font-medium">License type</span>
                            <select
                                v-model.number="offering.license_type_id"
                                class="h-10 w-full rounded-md border bg-background px-3"
                            >
                                <option
                                    v-for="license in licenseTypes"
                                    :key="license.id"
                                    :value="license.id"
                                >
                                    {{ license.name }}
                                </option>
                            </select>
                        </label>

                        <label class="space-y-1.5 text-sm">
                            <span class="font-medium">Customer-facing name</span>
                            <input
                                v-model="offering.name"
                                class="h-10 w-full rounded-md border bg-background px-3"
                            />
                        </label>

                        <label class="space-y-1.5 text-sm">
                            <span class="font-medium">Price</span>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted-foreground">$</span>
                                <input
                                    :value="priceValue(offering)"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="h-10 w-full rounded-md border bg-background pl-7 pr-3"
                                    @input="updatePrice(offering, $event)"
                                />
                            </div>
                        </label>

                        <label class="space-y-1.5 text-sm">
                            <span class="font-medium">Currency</span>
                            <input
                                v-model="offering.currency"
                                maxlength="3"
                                class="h-10 w-full rounded-md border bg-background px-3 uppercase"
                            />
                        </label>

                        <label class="space-y-1.5 text-sm">
                            <span class="font-medium">Download limit</span>
                            <input
                                v-model.number="offering.download_limit"
                                type="number"
                                min="1"
                                class="h-10 w-full rounded-md border bg-background px-3"
                                placeholder="Unlimited"
                            />
                        </label>

                        <label class="space-y-1.5 text-sm">
                            <span class="font-medium">Expires after days</span>
                            <input
                                v-model.number="offering.expires_after_days"
                                type="number"
                                min="1"
                                class="h-10 w-full rounded-md border bg-background px-3"
                                placeholder="Never"
                            />
                        </label>
                    </div>

                    <label class="flex items-start gap-3 rounded-lg border p-3 text-sm">
                        <input v-model="offering.is_active" type="checkbox" class="mt-1" />
                        <span>
                            <span class="font-medium">Active offering</span>
                            <span class="mt-0.5 block text-muted-foreground">
                                Active offerings can be shown to customers once the public asset experience is enabled.
                            </span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-lg border p-3 text-sm">
                        <input
                            v-model="offering.include_all_active_files"
                            type="checkbox"
                            class="mt-1"
                        />
                        <span>
                            <span class="font-medium">Include all active downloadable files automatically</span>
                            <span class="mt-0.5 block text-muted-foreground">
                                New downloadable files added later will automatically become part of this package.
                            </span>
                        </span>
                    </label>

                    <div v-if="!offering.include_all_active_files" class="space-y-3">
                        <div>
                            <h4 class="font-medium">Included files</h4>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Select the exact deliverables customers receive with this offering.
                            </p>
                        </div>

                        <div class="grid gap-2 lg:grid-cols-2">
                            <label
                                v-for="file in files"
                                :key="file.id"
                                class="flex gap-3 rounded-lg border p-3 text-sm transition-colors"
                                :class="[
                                    offering.file_ids.includes(file.id) ? 'border-primary/50 bg-primary/5' : '',
                                    !file.is_downloadable ? 'cursor-not-allowed opacity-55' : 'cursor-pointer',
                                ]"
                            >
                                <input
                                    v-model="offering.file_ids"
                                    type="checkbox"
                                    :value="file.id"
                                    :disabled="!file.is_downloadable"
                                    class="mt-1"
                                />
                                <span class="min-w-0">
                                    <span class="block truncate font-medium">{{ file.original_filename }}</span>
                                    <span class="mt-0.5 block text-xs capitalize text-muted-foreground">
                                        {{ file.extension.toUpperCase() }} · {{ formatRole(file.role) }} · {{ formatFileSize(file) }}
                                    </span>
                                    <span v-if="!file.is_downloadable" class="mt-1 block text-xs text-destructive">
                                        Mark this file as downloadable before including it.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <aside class="rounded-xl border bg-muted/20 p-4">
                    <h4 class="font-medium">Customer Package Preview</h4>

                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg border bg-background p-3">
                            <dt class="text-xs text-muted-foreground">Files</dt>
                            <dd class="mt-1 text-lg font-semibold">{{ selectedFiles(offering).length }}</dd>
                        </div>
                        <div class="rounded-lg border bg-background p-3">
                            <dt class="text-xs text-muted-foreground">Package size</dt>
                            <dd class="mt-1 text-lg font-semibold">{{ formatBytes(totalBytes(offering)) }}</dd>
                        </div>
                        <div class="rounded-lg border bg-background p-3">
                            <dt class="text-xs text-muted-foreground">Downloads</dt>
                            <dd class="mt-1 font-medium">{{ offering.download_limit ?? 'Unlimited' }}</dd>
                        </div>
                        <div class="rounded-lg border bg-background p-3">
                            <dt class="text-xs text-muted-foreground">Expiration</dt>
                            <dd class="mt-1 font-medium">
                                {{ offering.expires_after_days ? `${offering.expires_after_days} days` : 'Never' }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-4">
                        <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            Customer receives
                        </div>
                        <ul v-if="selectedFiles(offering).length" class="mt-2 space-y-2">
                            <li
                                v-for="file in selectedFiles(offering)"
                                :key="file.id"
                                class="rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <div class="truncate font-medium">{{ file.original_filename }}</div>
                                <div class="mt-0.5 text-xs capitalize text-muted-foreground">
                                    {{ file.extension.toUpperCase() }} · {{ formatRole(file.role) }}
                                </div>
                            </li>
                        </ul>
                        <p v-else class="mt-2 rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                            No files are currently included.
                        </p>
                    </div>
                </aside>
            </div>
        </div>

        <Button
            type="button"
            variant="outline"
            :disabled="!canAdd"
            @click="addOffering"
        >
            Add License Offering
        </Button>

        <p v-if="!canAdd" class="text-sm text-muted-foreground">
            Every available license type already has an offering.
        </p>

        <ConfirmActionDialog
            :open="removeIndex !== null"
            title="Remove license offering?"
            description="This removes the offering from the current form. Save Offerings to apply the change. Existing completed purchases are not changed."
            confirm-label="Remove Offering"
            destructive
            @update:open="(open) => { if (!open) removeIndex = null; }"
            @confirm="confirmRemoveOffering"
            @cancel="removeIndex = null"
        />
    </div>
</template>

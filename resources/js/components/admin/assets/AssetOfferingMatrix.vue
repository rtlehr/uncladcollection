<script setup lang="ts">
import { AlertTriangle, Check, Files, PackageCheck, X } from '@lucide/vue';
import { computed } from 'vue';
import type { AdminAssetFile, AdminAssetOffering } from '@/types/adminAsset';

const props = defineProps<{
    files: AdminAssetFile[];
    offerings: AdminAssetOffering[];
}>();

const activeOfferings = computed(() => props.offerings.filter((offering) => offering.is_active));
const activeFiles = computed(() => props.files.filter((file) => file.is_active));
const downloadableFiles = computed(() => activeFiles.value.filter((file) => file.is_downloadable));

function includedFiles(offering: AdminAssetOffering): AdminAssetFile[] {
    return downloadableFiles.value.filter((file) =>
        offering.include_all_active_files || offering.file_ids.includes(file.id),
    );
}

function includesFile(offering: AdminAssetOffering, file: AdminAssetFile): boolean {
    return file.is_downloadable && includedFiles(offering).some((item) => item.id === file.id);
}

function formatPrice(offering: AdminAssetOffering): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: offering.currency || 'USD',
    }).format(offering.price_cents / 100);
}

function formatBytes(bytes: number): string {
    if (bytes >= 1024 ** 3) {
return `${(bytes / 1024 ** 3).toFixed(2)} GB`;
}

    if (bytes >= 1024 ** 2) {
return `${(bytes / 1024 ** 2).toFixed(2)} MB`;
}

    if (bytes >= 1024) {
return `${(bytes / 1024).toFixed(1)} KB`;
}

    return `${bytes} B`;
}

function packageBytes(offering: AdminAssetOffering): number {
    return includedFiles(offering).reduce((sum, file) => sum + (file.size_bytes ?? 0), 0);
}

function uniqueFormats(offering: AdminAssetOffering): string[] {
    return Array.from(new Set(includedFiles(offering).map((file) => file.extension.toUpperCase())));
}

function warningFor(offering: AdminAssetOffering): string | null {
    if (!includedFiles(offering).length) {
return 'No downloadable files are included.';
}

    if (offering.price_cents <= 0) {
return 'This offering has no price.';
}

    return null;
}
</script>

<template>
    <div class="space-y-5">
        <div class="grid gap-4 lg:grid-cols-3">
            <article
                v-for="offering in activeOfferings"
                :key="offering.id ?? `${offering.license_type_id}-${offering.name}`"
                class="overflow-hidden rounded-xl border bg-background"
            >
                <div class="border-b bg-muted/20 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold">{{ offering.name }}</h3>
                            <p class="mt-1 text-2xl font-semibold">{{ formatPrice(offering) }}</p>
                        </div>
                        <span v-if="!warningFor(offering)" class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                            <PackageCheck class="h-3.5 w-3.5" /> Ready
                        </span>
                        <span v-else class="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2 py-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                            <AlertTriangle class="h-3.5 w-3.5" /> Review
                        </span>
                    </div>
                </div>

                <div class="space-y-4 p-4">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg border p-3">
                            <div class="text-xs text-muted-foreground">Files</div>
                            <div class="mt-1 font-semibold">{{ includedFiles(offering).length }}</div>
                        </div>
                        <div class="rounded-lg border p-3">
                            <div class="text-xs text-muted-foreground">Package size</div>
                            <div class="mt-1 font-semibold">{{ formatBytes(packageBytes(offering)) }}</div>
                        </div>
                        <div class="rounded-lg border p-3">
                            <div class="text-xs text-muted-foreground">Downloads</div>
                            <div class="mt-1 font-semibold">{{ offering.download_limit ?? 'Unlimited' }}</div>
                        </div>
                        <div class="rounded-lg border p-3">
                            <div class="text-xs text-muted-foreground">Expires</div>
                            <div class="mt-1 font-semibold">{{ offering.expires_after_days ? `${offering.expires_after_days} days` : 'Never' }}</div>
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Formats</div>
                        <div v-if="uniqueFormats(offering).length" class="mt-2 flex flex-wrap gap-1.5">
                            <span v-for="format in uniqueFormats(offering)" :key="format" class="rounded-full border bg-muted/20 px-2 py-1 text-xs font-medium">{{ format }}</span>
                        </div>
                        <p v-else class="mt-2 text-sm text-muted-foreground">No formats included.</p>
                    </div>

                    <div v-if="warningFor(offering)" class="rounded-lg border border-amber-500/30 bg-amber-500/5 p-3 text-sm text-amber-800 dark:text-amber-200">
                        {{ warningFor(offering) }}
                    </div>
                </div>
            </article>

            <div v-if="!activeOfferings.length" class="col-span-full rounded-xl border border-dashed p-8 text-center text-muted-foreground">
                <Files class="mx-auto mb-3 h-8 w-8" />
                No active offerings are currently available for comparison.
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border">
            <div class="border-b bg-muted/30 px-4 py-3">
                <h3 class="font-medium">File Coverage Matrix</h3>
                <p class="mt-1 text-sm text-muted-foreground">Confirm exactly which deliverables are included in every active license.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr>
                            <th class="sticky left-0 z-10 min-w-64 bg-muted/40 px-4 py-3 font-medium">Asset file</th>
                            <th v-for="offering in activeOfferings" :key="offering.id ?? `${offering.license_type_id}-${offering.name}`" class="min-w-40 px-4 py-3 text-center font-medium">
                                <div>{{ offering.name }}</div>
                                <div class="mt-0.5 text-xs font-normal text-muted-foreground">{{ formatPrice(offering) }}</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="file in activeFiles" :key="file.id" class="border-t">
                            <td class="sticky left-0 z-10 bg-background px-4 py-3">
                                <div class="font-medium">{{ file.original_filename }}</div>
                                <div class="mt-0.5 text-xs capitalize text-muted-foreground">
                                    {{ file.extension.toUpperCase() }} · {{ file.role.replaceAll('_', ' ') }}
                                    <span v-if="!file.is_downloadable"> · Private</span>
                                </div>
                            </td>
                            <td v-for="offering in activeOfferings" :key="`${file.id}-${offering.id ?? offering.license_type_id}`" class="px-4 py-3 text-center">
                                <span v-if="includesFile(offering, file)" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-300" aria-label="Included"><Check class="h-4 w-4" /></span>
                                <span v-else-if="!file.is_downloadable" class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-muted text-muted-foreground" aria-label="Private file"><X class="h-3.5 w-3.5" /></span>
                                <span v-else class="text-muted-foreground/60">—</span>
                            </td>
                        </tr>
                        <tr v-if="!activeFiles.length">
                            <td :colspan="Math.max(activeOfferings.length + 1, 1)" class="px-4 py-8 text-center text-muted-foreground">Add active files to populate the matrix.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

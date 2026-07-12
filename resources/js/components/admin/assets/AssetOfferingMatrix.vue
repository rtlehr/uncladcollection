<script setup lang="ts">
import { computed } from 'vue';
import type { AdminAssetFile, AdminAssetOffering } from '@/types/adminAsset';

const props = defineProps<{
    files: AdminAssetFile[];
    offerings: AdminAssetOffering[];
}>();

const activeOfferings = computed(() => props.offerings.filter((offering) => offering.is_active));
const activeFiles = computed(() => props.files.filter((file) => file.is_active));

function includesFile(offering: AdminAssetOffering, file: AdminAssetFile): boolean {
    if (!file.is_downloadable) {
        return false;
    }

    return offering.include_all_active_files
        ? file.is_active
        : offering.file_ids.includes(file.id);
}

function fileLabel(file: AdminAssetFile): string {
    return `${file.extension.toUpperCase()} · ${file.role.replaceAll('_', ' ')}`;
}
</script>

<template>
    <div class="overflow-hidden rounded-xl border">
        <div class="border-b bg-muted/30 px-4 py-3">
            <h3 class="font-medium">License Coverage Matrix</h3>
            <p class="mt-1 text-sm text-muted-foreground">
                Review which downloadable files are included in each active offering.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-muted/40 text-left">
                    <tr>
                        <th class="min-w-64 px-4 py-3 font-medium">Asset file</th>
                        <th
                            v-for="offering in activeOfferings"
                            :key="offering.id ?? `${offering.license_type_id}-${offering.name}`"
                            class="min-w-36 px-4 py-3 text-center font-medium"
                        >
                            {{ offering.name }}
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="file in activeFiles"
                        :key="file.id"
                        class="border-t"
                    >
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ file.original_filename }}</div>
                            <div class="mt-0.5 text-xs capitalize text-muted-foreground">
                                {{ fileLabel(file) }}
                                <span v-if="!file.is_downloadable"> · Not downloadable</span>
                            </div>
                        </td>

                        <td
                            v-for="offering in activeOfferings"
                            :key="`${file.id}-${offering.id ?? offering.license_type_id}`"
                            class="px-4 py-3 text-center"
                        >
                            <span
                                v-if="includesFile(offering, file)"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary/10 font-semibold text-primary"
                                aria-label="Included"
                            >
                                ✓
                            </span>
                            <span v-else class="text-muted-foreground/60">—</span>
                        </td>
                    </tr>

                    <tr v-if="!activeFiles.length">
                        <td
                            :colspan="Math.max(activeOfferings.length + 1, 1)"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Add active files to this asset to populate the matrix.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="!activeOfferings.length"
            class="border-t px-4 py-4 text-sm text-muted-foreground"
        >
            No active offerings are currently available for comparison.
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CircleCheck, CircleAlert } from '@lucide/vue';
import { ref } from 'vue';

import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import type { AdminAsset, SelectOption } from '@/types/adminAsset';

const props = defineProps<{
    assets: AdminAsset[];
    filters: {
        search: string;
        type: string;
        status: string;
    };
    assetTypes: SelectOption[];
    statuses: SelectOption[];
}>();

const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? '');
const status = ref(props.filters.status ?? '');

function reload(): void {
    router.get(
        '/admin/assets',
        {
            search: search.value || undefined,
            asset_type: type.value || undefined,
            status: status.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

function createAsset(): void {
    router.visit('/admin/assets/create');
}

function editAsset(assetId: number): void {
    router.visit(`/admin/assets/${assetId}/edit`);
}
</script>

<template>
    <Head title="Assets" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="Assets"
            description="Manage multi-file digital assets and their processing state."
        />

        <div class="flex flex-wrap gap-3 rounded-xl border p-4">
            <input
                v-model="search"
                class="h-10 min-w-64 rounded-md border bg-background px-3 text-sm"
                placeholder="Search assets..."
                @keyup.enter="reload"
            />

            <select
                v-model="type"
                class="h-10 rounded-md border bg-background px-3 text-sm"
                @change="reload"
            >
                <option value="">All types</option>

                <option
                    v-for="option in assetTypes"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>

            <select
                v-model="status"
                class="h-10 rounded-md border bg-background px-3 text-sm"
                @change="reload"
            >
                <option value="">All statuses</option>

                <option
                    v-for="option in statuses"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>

            <Button
                type="button"
                variant="outline"
                @click="reload"
            >
                Search
            </Button>

            <Button
                type="button"
                class="ml-auto"
                @click="createAsset"
            >
                Create Asset
            </Button>
        </div>

        <div class="overflow-hidden rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">Asset</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Files</th>
                        <th class="p-3">Collection</th><th class="p-3">Health</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="asset in assets"
                        :key="asset.id"
                        class="border-t"
                    >
                        <td class="p-3">
                            <div class="font-medium">
                                {{ asset.title }}
                            </div>

                            <div class="text-xs text-muted-foreground">
                                {{ asset.slug }}
                            </div>
                        </td>

                        <td class="p-3">
                            {{ asset.asset_type.replace('_', ' ') }}
                        </td>

                        <td class="p-3">
                            <StatusBadge :status="asset.status" />
                        </td>

                        <td class="p-3">
                            {{ asset.active_files_count }} active
                        </td>

                        <td class="p-3">
                            {{ asset.collection?.name ?? '—' }}
                        </td>

                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                <CircleCheck v-if="asset.health.score >= 90" class="h-4 w-4 text-emerald-600" />
                                <CircleAlert v-else class="h-4 w-4 text-amber-600" />
                                <span class="font-medium tabular-nums">{{ asset.health.score }}%</span>
                            </div>
                            <div class="mt-1 h-1.5 w-24 overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full bg-primary" :style="{ width: `${asset.health.score}%` }" />
                            </div>
                        </td>

                        <td class="p-3 text-right">
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="editAsset(asset.id)"
                            >
                                Edit
                            </Button>
                        </td>
                    </tr>

                    <tr v-if="!assets.length">
                        <td
                            colspan="7"
                            class="p-8 text-center text-muted-foreground"
                        >
                            No assets found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
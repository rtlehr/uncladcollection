<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Boxes, Check, ImageIcon, Search, X } from '@lucide/vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type AssetCardOption = {
    id: number;
    title: string;
    slug: string;
    photographer: string | null;
    thumbnail_url: string | null;
    public_url: string | null;
    asset_type_label?: string | null;
    formats?: string[];
};

export type SmartAssetCardInsert = {
    assetId: number;
    assetSlug: string;
    layout: 'compact' | 'standard' | 'featured';
    heading: string;
    description: string;
};

const props = defineProps<{ open: boolean }>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    insert: [payload: SmartAssetCardInsert];
}>();

const search = ref('');
const assets = ref<AssetCardOption[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const selected = ref<AssetCardOption | null>(null);
const layout = ref<'compact' | 'standard' | 'featured'>('standard');
const heading = ref('');
const description = ref('');

const layoutOptions = [
    { value: 'compact', label: 'Compact', detail: 'Best inside article flow' },
    { value: 'standard', label: 'Standard', detail: 'Balanced image and details' },
    { value: 'featured', label: 'Featured', detail: 'Full-width visual callout' },
] as const;

watch(
    () => props.open,
    async (open) => {
        if (!open) return;
        selected.value = null;
        layout.value = 'standard';
        heading.value = '';
        description.value = '';
        await searchAssets();
    },
);

const canInsert = computed(() => selected.value !== null);
const previewFormats = computed(() => selected.value?.formats?.slice(0, 5) ?? []);

async function searchAssets(): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();
        if (search.value.trim()) params.set('search', search.value.trim());

        const response = await fetch(
            `/admin/blog-posts/image-library?${params.toString()}`,
            { headers: { Accept: 'application/json' } },
        );

        if (!response.ok) {
            const data = await response.json().catch(() => null);
            throw new Error(data?.message ?? `Unable to load Assets (${response.status}).`);
        }

        const data = await response.json();
        assets.value = data.images ?? [];
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : 'Unable to load Assets.';
    } finally {
        loading.value = false;
    }
}

function choose(asset: AssetCardOption): void {
    selected.value = asset;
    heading.value = asset.title;
}

function insert(): void {
    if (!selected.value) return;

    emit('insert', {
        assetId: selected.value.id,
        assetSlug: selected.value.slug,
        layout: layout.value,
        heading: heading.value.trim(),
        description: description.value.trim(),
    });
    emit('update:open', false);
}
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-3 backdrop-blur-sm sm:p-5"
        role="dialog"
        aria-modal="true"
        aria-labelledby="smart-asset-dialog-title"
        @keydown.esc="emit('update:open', false)"
    >
        <div class="flex max-h-[94vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl border bg-background shadow-2xl">
            <header class="flex items-start justify-between gap-5 border-b px-5 py-4 sm:px-6">
                <div class="min-w-0">
                    <div class="mb-1 flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-primary">
                        <Boxes class="h-4 w-4" /> Marketplace content
                    </div>
                    <h2 id="smart-asset-dialog-title" class="text-xl font-semibold sm:text-2xl">
                        Insert Smart Asset Card
                    </h2>
                    <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                        Select a published Asset, choose a layout, and preview how it will sit inside the article.
                    </p>
                </div>
                <Button type="button" size="icon" variant="ghost" aria-label="Close Asset Card dialog" @click="emit('update:open', false)">
                    <X class="h-5 w-5" />
                </Button>
            </header>

            <div class="grid min-h-0 flex-1 lg:grid-cols-[minmax(0,1.05fr)_minmax(390px,0.95fr)]">
                <section class="min-h-0 overflow-y-auto border-b p-5 lg:border-b-0 lg:border-r lg:p-6" aria-label="Asset selection">
                    <div class="sticky top-0 z-10 mb-5 flex gap-2 bg-background pb-3">
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="search" class="pl-9" placeholder="Search by title, photographer, or keyword..." aria-label="Search Assets" @keyup.enter="searchAssets" />
                        </div>
                        <Button type="button" @click="searchAssets">Search</Button>
                    </div>

                    <div v-if="error" class="rounded-xl border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">{{ error }}</div>
                    <div v-else-if="loading" class="grid min-h-64 place-items-center rounded-xl border border-dashed text-sm text-muted-foreground">Loading Assets...</div>
                    <div v-else-if="assets.length === 0" class="grid min-h-64 place-items-center rounded-xl border border-dashed px-6 text-center text-sm text-muted-foreground">No published Assets matched your search.</div>

                    <div v-else class="grid gap-3 sm:grid-cols-2">
                        <button
                            v-for="asset in assets"
                            :key="asset.id"
                            type="button"
                            class="group relative overflow-hidden rounded-xl border bg-card text-left shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary/60 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            :class="selected?.id === asset.id ? 'border-primary ring-2 ring-primary/20' : ''"
                            :aria-pressed="selected?.id === asset.id"
                            @click="choose(asset)"
                        >
                            <span v-if="selected?.id === asset.id" class="absolute right-2 top-2 z-10 grid h-7 w-7 place-items-center rounded-full bg-primary text-primary-foreground shadow"><Check class="h-4 w-4" /></span>
                            <div class="aspect-[16/9] overflow-hidden bg-muted">
                                <img v-if="asset.thumbnail_url" :src="asset.thumbnail_url" :alt="asset.title" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]" />
                                <div v-else class="grid h-full place-items-center text-muted-foreground"><ImageIcon class="h-8 w-8" /></div>
                            </div>
                            <div class="p-3.5">
                                <p class="line-clamp-2 text-sm font-semibold leading-snug">{{ asset.title }}</p>
                                <div class="mt-2 flex items-center justify-between gap-3 text-xs text-muted-foreground">
                                    <span class="truncate">{{ asset.photographer ?? 'Unclad Collection' }}</span>
                                    <span v-if="asset.asset_type_label" class="shrink-0 rounded-full bg-muted px-2 py-0.5 font-semibold">{{ asset.asset_type_label }}</span>
                                </div>
                            </div>
                        </button>
                    </div>
                </section>

                <section class="min-h-0 overflow-y-auto bg-muted/20 p-5 lg:p-6" aria-label="Asset Card configuration">
                    <div v-if="selected" class="space-y-5">
                        <div>
                            <span class="text-sm font-semibold">Card layout</span>
                            <div class="mt-2 grid gap-2 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                                <button
                                    v-for="option in layoutOptions"
                                    :key="option.value"
                                    type="button"
                                    class="rounded-xl border bg-background p-3 text-left transition hover:border-primary/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    :class="layout === option.value ? 'border-primary ring-2 ring-primary/15' : ''"
                                    :aria-pressed="layout === option.value"
                                    @click="layout = option.value"
                                >
                                    <span class="block text-sm font-semibold">{{ option.label }}</span>
                                    <span class="mt-0.5 block text-xs leading-snug text-muted-foreground">{{ option.detail }}</span>
                                </button>
                            </div>
                        </div>

                        <label class="block text-sm">
                            <span class="mb-1.5 block font-semibold">Card heading</span>
                            <Input v-model="heading" maxlength="255" />
                        </label>

                        <label class="block text-sm">
                            <span class="mb-1.5 block font-semibold">Optional article context</span>
                            <textarea v-model="description" class="min-h-24 w-full resize-y rounded-md border bg-background px-3 py-2 outline-none transition focus-visible:ring-2 focus-visible:ring-ring" maxlength="500" placeholder="Explain why this Asset is relevant to the article..." />
                            <span class="mt-1 block text-right text-xs text-muted-foreground">{{ description.length }}/500</span>
                        </label>

                        <div class="rounded-xl border bg-background p-3 shadow-sm">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <span class="text-xs font-bold uppercase tracking-[0.14em] text-muted-foreground">Article preview</span>
                                <span class="rounded-full bg-primary/10 px-2 py-1 text-[11px] font-bold capitalize text-primary">{{ layout }}</span>
                            </div>

                            <article class="overflow-hidden rounded-xl border bg-card shadow-sm" :class="layout === 'compact' ? 'grid grid-cols-[120px_1fr]' : ''">
                                <div class="relative overflow-hidden bg-muted" :class="layout === 'featured' ? 'aspect-[16/7]' : layout === 'compact' ? 'min-h-40' : 'aspect-video'">
                                    <img v-if="selected.thumbnail_url" :src="selected.thumbnail_url" :alt="selected.title" class="h-full w-full object-cover" />
                                    <div v-else class="grid h-full place-items-center text-muted-foreground"><ImageIcon class="h-9 w-9" /></div>
                                    <span class="absolute left-2 top-2 rounded-full bg-black/70 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-white backdrop-blur">Live Asset</span>
                                </div>
                                <div class="flex min-w-0 flex-col p-4">
                                    <div class="text-[11px] font-bold uppercase tracking-[0.13em] text-primary">{{ selected.asset_type_label ?? 'Marketplace Asset' }}</div>
                                    <h3 class="mt-1 line-clamp-2 text-base font-semibold leading-tight">{{ heading || selected.title }}</h3>
                                    <p v-if="description" class="mt-2 line-clamp-3 text-xs leading-relaxed text-muted-foreground">{{ description }}</p>
                                    <p class="mt-2 truncate text-xs text-muted-foreground">By {{ selected.photographer ?? 'Unclad Collection' }}</p>
                                    <div v-if="previewFormats.length" class="mt-3 flex flex-wrap gap-1.5">
                                        <span v-for="format in previewFormats" :key="format" class="rounded-full border bg-muted/60 px-2 py-1 text-[10px] font-bold">{{ format }}</span>
                                    </div>
                                    <div class="mt-auto flex items-end justify-between gap-3 pt-4">
                                        <div><span class="block text-[10px] font-bold uppercase text-muted-foreground">Live pricing</span><strong class="text-sm">Loaded when published</strong></div>
                                        <span class="rounded-md bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground">License asset</span>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div v-else class="grid min-h-[420px] place-items-center rounded-2xl border border-dashed bg-background px-8 text-center">
                        <div><div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-primary/10 text-primary"><Boxes class="h-7 w-7" /></div><h3 class="mt-4 font-semibold">Choose an Asset</h3><p class="mt-1 max-w-xs text-sm text-muted-foreground">Select a published Asset from the library to configure and preview its Smart Asset Card.</p></div>
                    </div>
                </section>
            </div>

            <footer class="flex flex-col-reverse gap-2 border-t bg-background px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="text-xs text-muted-foreground">Pricing, formats, and license offerings stay synchronized with the Marketplace.</p>
                <div class="flex justify-end gap-2">
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Cancel</Button>
                    <Button type="button" :disabled="!canInsert" @click="insert">Insert Asset Card</Button>
                </div>
            </footer>
        </div>
    </div>
</template>

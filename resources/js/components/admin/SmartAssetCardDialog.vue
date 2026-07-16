<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Boxes, Search, X } from '@lucide/vue';

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

async function searchAssets(): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();

        if (search.value.trim()) {
            params.set('search', search.value.trim());
        }

        const response = await fetch(
            `/admin/blog-posts/image-library?${params.toString()}`,
            { headers: { Accept: 'application/json' } },
        );

        if (!response.ok) {
            const data = await response.json().catch(() => null);
            throw new Error(
                data?.message ?? `Unable to load Assets (${response.status}).`,
            );
        }

        const data = await response.json();
        assets.value = data.images ?? [];
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load Assets.';
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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
        <div
            class="max-h-[92vh] w-full max-w-6xl overflow-hidden rounded-xl border bg-background shadow-2xl"
        >
            <div class="flex items-center justify-between border-b p-5">
                <div>
                    <h2 class="flex items-center gap-2 text-xl font-semibold">
                        <Boxes class="h-5 w-5" />
                        Insert Smart Asset Card
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Choose an Asset and configure how its live card appears
                        in the article.
                    </p>
                </div>

                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    @click="emit('update:open', false)"
                >
                    <X class="h-4 w-4" />
                </Button>
            </div>

            <div class="grid max-h-[72vh] lg:grid-cols-[1.1fr_0.9fr]">
                <section class="overflow-y-auto border-r p-5">
                    <div class="mb-4 flex gap-2">
                        <div class="relative flex-1">
                            <Search
                                class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground"
                            />
                            <Input
                                v-model="search"
                                class="pl-9"
                                placeholder="Search Assets..."
                                @keyup.enter="searchAssets"
                            />
                        </div>
                        <Button type="button" @click="searchAssets">
                            Search
                        </Button>
                    </div>

                    <div
                        v-if="error"
                        class="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
                    >
                        {{ error }}
                    </div>

                    <div
                        v-else-if="loading"
                        class="py-12 text-center text-sm text-muted-foreground"
                    >
                        Loading Assets...
                    </div>

                    <div
                        v-else
                        class="grid gap-3 sm:grid-cols-2"
                    >
                        <button
                            v-for="asset in assets"
                            :key="asset.id"
                            type="button"
                            class="overflow-hidden rounded-lg border text-left transition hover:border-primary"
                            :class="
                                selected?.id === asset.id
                                    ? 'border-primary ring-2 ring-primary/20'
                                    : ''
                            "
                            @click="choose(asset)"
                        >
                            <div class="aspect-[16/9] bg-muted">
                                <img
                                    v-if="asset.thumbnail_url"
                                    :src="asset.thumbnail_url"
                                    :alt="asset.title"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div class="p-3">
                                <p class="line-clamp-2 text-sm font-semibold">
                                    {{ asset.title }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ asset.photographer ?? 'Unclad Collection' }}
                                </p>
                            </div>
                        </button>
                    </div>
                </section>

                <section class="overflow-y-auto p-5">
                    <div v-if="selected" class="space-y-5">
                        <div>
                            <label class="text-sm font-medium">Layout</label>
                            <div class="mt-2 grid grid-cols-3 gap-2">
                                <Button
                                    v-for="option in ['compact', 'standard', 'featured']"
                                    :key="option"
                                    type="button"
                                    size="sm"
                                    :variant="layout === option ? 'default' : 'outline'"
                                    class="capitalize"
                                    @click="
                                        layout = option as
                                            | 'compact'
                                            | 'standard'
                                            | 'featured'
                                    "
                                >
                                    {{ option }}
                                </Button>
                            </div>
                        </div>

                        <label class="block text-sm">
                            <span class="mb-1 block font-medium">
                                Card heading
                            </span>
                            <Input v-model="heading" maxlength="255" />
                        </label>

                        <label class="block text-sm">
                            <span class="mb-1 block font-medium">
                                Optional description
                            </span>
                            <textarea
                                v-model="description"
                                class="min-h-24 w-full rounded-md border bg-background px-3 py-2"
                                maxlength="500"
                                placeholder="Why this Asset matters to the article..."
                            />
                        </label>

                        <div
                            class="overflow-hidden rounded-xl border bg-card shadow-sm"
                            :class="
                                layout === 'featured'
                                    ? ''
                                    : layout === 'compact'
                                      ? 'grid grid-cols-[120px_1fr]'
                                      : ''
                            "
                        >
                            <img
                                v-if="selected.thumbnail_url"
                                :src="selected.thumbnail_url"
                                :alt="selected.title"
                                class="object-cover"
                                :class="
                                    layout === 'compact'
                                        ? 'h-full min-h-32 w-full'
                                        : 'aspect-video w-full'
                                "
                            />
                            <div class="space-y-2 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wider text-primary">
                                    Smart Asset Card
                                </p>
                                <h3 class="text-lg font-semibold">
                                    {{ heading || selected.title }}
                                </h3>
                                <p
                                    v-if="description"
                                    class="text-sm text-muted-foreground"
                                >
                                    {{ description }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    By {{ selected.photographer ?? 'Unclad Collection' }}
                                </p>
                                <Button type="button" size="sm">
                                    View Asset
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex min-h-80 items-center justify-center rounded-xl border border-dashed text-sm text-muted-foreground"
                    >
                        Select an Asset to configure the card.
                    </div>
                </section>
            </div>

            <div class="flex justify-end gap-2 border-t p-4">
                <Button
                    type="button"
                    variant="outline"
                    @click="emit('update:open', false)"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    :disabled="!canInsert"
                    @click="insert"
                >
                    Insert Asset Card
                </Button>
            </div>
        </div>
    </div>
</template>

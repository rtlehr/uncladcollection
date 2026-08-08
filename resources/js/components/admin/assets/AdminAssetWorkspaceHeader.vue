<script setup lang="ts">
import { ExternalLink, FileStack, Layers3, PackageCheck, Sparkles } from '@lucide/vue';
import { computed } from 'vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import type { AdminAsset } from '@/types/adminAsset';

const props = defineProps<{
    asset: AdminAsset;
    canViewPublicPage: boolean;
}>();

const emit = defineEmits<{
    viewPublicPage: [];
}>();

const imageUrl = computed(
    () => props.asset.marketplace_image_url || props.asset.preview_url,
);

const relationshipCount = computed(
    () => props.asset.file_relationships?.length ?? 0,
);

const offeringCount = computed(() => props.asset.offerings?.length ?? 0);

const configurationCount = computed(
    () => props.asset.configurations?.length ?? 0,
);
</script>

<template>
    <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
        <div class="grid lg:grid-cols-[280px_minmax(0,1fr)]">
            <div class="relative aspect-[16/9] bg-muted lg:aspect-auto lg:min-h-[220px]">
                <img
                    v-if="imageUrl"
                    :src="imageUrl"
                    :alt="asset.title"
                    class="absolute inset-0 h-full w-full object-cover"
                />
                <div
                    v-else
                    class="absolute inset-0 flex items-center justify-center text-muted-foreground"
                >
                    <FileStack class="h-12 w-12" />
                </div>
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/65 to-transparent p-4 pt-12 text-white lg:hidden">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] opacity-80">
                        {{ asset.asset_type }}
                    </p>
                    <h1 class="mt-1 text-xl font-semibold">{{ asset.title }}</h1>
                </div>
            </div>

            <div class="flex min-w-0 flex-col p-5 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusBadge :status="asset.status" />
                            <span
                                v-if="asset.is_featured"
                                class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium"
                            >
                                <Sparkles class="h-3.5 w-3.5" /> Featured
                            </span>
                            <span
                                v-if="asset.is_ai_generated"
                                class="rounded-full border px-2.5 py-1 text-xs font-medium"
                            >
                                AI Generated
                            </span>
                        </div>
                        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                            {{ asset.asset_type }} workspace
                        </p>
                        <h1 class="mt-1 hidden truncate text-3xl font-semibold tracking-tight lg:block">
                            {{ asset.title }}
                        </h1>
                        <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                            Manage presentation, deliverable files, relationships, customer options, and license offerings from one workspace.
                        </p>
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        :disabled="!canViewPublicPage"
                        :title="canViewPublicPage ? 'Open the public asset page in a new tab' : 'Publish and activate this asset before viewing its public page'"
                        @click="emit('viewPublicPage')"
                    >
                        <ExternalLink class="mr-2 h-4 w-4" />
                        View Public Page
                    </Button>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border bg-background/70 p-3">
                        <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <FileStack class="h-4 w-4" /> Files
                        </div>
                        <p class="mt-1 text-2xl font-semibold tabular-nums">{{ asset.active_files_count }}</p>
                    </div>
                    <div class="rounded-xl border bg-background/70 p-3">
                        <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <Layers3 class="h-4 w-4" /> Relationships
                        </div>
                        <p class="mt-1 text-2xl font-semibold tabular-nums">{{ relationshipCount }}</p>
                    </div>
                    <div class="rounded-xl border bg-background/70 p-3">
                        <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <PackageCheck class="h-4 w-4" /> Offerings
                        </div>
                        <p class="mt-1 text-2xl font-semibold tabular-nums">{{ offeringCount }}</p>
                    </div>
                    <div class="rounded-xl border bg-background/70 p-3">
                        <div class="flex items-center justify-between gap-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            <span>Readiness</span>
                            <span>{{ configurationCount }} options</span>
                        </div>
                        <p class="mt-1 text-2xl font-semibold tabular-nums">{{ asset.health.score }}%</p>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary transition-all"
                                :style="{ width: `${asset.health.score}%` }"
                            />
                        </div>
                    </div>
                </div>

                <p v-if="!canViewPublicPage" class="mt-4 text-xs text-muted-foreground">
                    Publish and activate this asset to enable its public page.
                </p>
            </div>
        </div>
    </section>
</template>

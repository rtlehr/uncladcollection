<script setup lang="ts">
import { BadgeCheck, FileStack, ShieldCheck, Sparkles } from '@lucide/vue';

import GallerySearchBar from '@/components/Gallery/GallerySearchBar.vue';
import type { PublicSearchSuggestion } from '@/types/publicSearch';

const search = defineModel<string>('search', { required: true });

defineProps<{
    total: number;
    suggestions: PublicSearchSuggestion[];
}>();

const emit = defineEmits<{
    search: [];
    suggestion: [suggestion: PublicSearchSuggestion];
}>();

function formatNumber(value: number): string {
    return Number(value ?? 0).toLocaleString();
}
</script>

<template>
    <section class="relative isolate overflow-hidden bg-[var(--brand-primary)] text-white">
        <div class="absolute inset-0 -z-20 bg-[linear-gradient(135deg,color-mix(in_srgb,var(--brand-primary)_92%,black),var(--brand-primary))]" />
        <div class="absolute inset-0 -z-10 opacity-90 bg-[radial-gradient(circle_at_14%_18%,color-mix(in_srgb,var(--brand-accent)_34%,transparent),transparent_30%),radial-gradient(circle_at_84%_4%,color-mix(in_srgb,var(--brand-secondary)_48%,transparent),transparent_38%)]" />
        <div class="absolute inset-x-0 bottom-0 h-px bg-white/10" />

        <div class="mx-auto max-w-[1440px] px-4 py-14 sm:px-8 sm:py-20 lg:px-12 lg:py-24">
            <div class="max-w-5xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.17em] text-white/85 backdrop-blur">
                    <Sparkles class="h-3.5 w-3.5 text-[var(--brand-accent)]" />
                    Digital asset marketplace
                </div>

                <h1 class="mt-6 max-w-4xl text-4xl font-semibold leading-[1.03] tracking-[-0.045em] sm:text-6xl lg:text-7xl">
                    Authentic media for thoughtful creative work
                </h1>

                <p class="mt-6 max-w-2xl text-base leading-8 text-white/72 sm:text-lg">
                    Discover licensed photography, video, vector artwork, and downloadable creative packages built around genuine naturist living.
                </p>
            </div>

            <div class="mt-9 max-w-4xl">
                <GallerySearchBar
                    v-model="search"
                    :suggestions="suggestions"
                    @search="emit('search')"
                    @suggestion="emit('suggestion', $event)"
                />
            </div>

            <div class="mt-7 flex flex-col gap-4 text-sm text-white/68 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-7">
                <span class="font-semibold text-white">
                    {{ formatNumber(total) }} marketplace assets
                </span>

                <span class="inline-flex items-center gap-2">
                    <ShieldCheck class="h-4 w-4 text-[var(--brand-accent)]" />
                    Clear licensing
                </span>

                <span class="inline-flex items-center gap-2">
                    <FileStack class="h-4 w-4 text-[var(--brand-accent)]" />
                    Multiple file formats
                </span>

                <span class="inline-flex items-center gap-2">
                    <BadgeCheck class="h-4 w-4 text-[var(--brand-accent)]" />
                    Curated collection
                </span>
            </div>
        </div>
    </section>
</template>

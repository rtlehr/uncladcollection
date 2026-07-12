<script setup lang="ts">
import GallerySearchBar from '@/components/Gallery/GallerySearchBar.vue';

const search = defineModel<string>('search', { required: true });

defineProps<{
    total: number;
}>();

const emit = defineEmits<{
    search: [];
}>();

function formatNumber(value: number): string {
    return Number(value ?? 0).toLocaleString();
}
</script>

<template>
    <section class="relative overflow-hidden bg-[var(--brand-primary)] text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,color-mix(in_srgb,var(--brand-accent)_24%,transparent),transparent_30%),radial-gradient(circle_at_85%_10%,color-mix(in_srgb,var(--brand-secondary)_42%,transparent),transparent_38%)]" />

        <div class="relative mx-auto max-w-[1440px] px-5 py-16 sm:px-8 sm:py-20 lg:px-12 lg:py-24">
            <div class="max-w-4xl">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-accent)]">
                    The image library
                </p>

                <h1 class="mt-5 text-4xl font-semibold leading-[1.06] tracking-[-0.04em] sm:text-6xl">
                    Explore authentic imagery with purpose
                </h1>

                <p class="mt-5 max-w-2xl text-base leading-8 text-white/75 sm:text-lg">
                    Browse respectful, community-centered photography created for publishers, resorts, clubs, advocates, and people who value honest representation.
                </p>
            </div>

            <div class="mt-9 max-w-3xl">
                <GallerySearchBar
                    v-model="search"
                    @search="emit('search')"
                />
            </div>

            <p class="mt-5 text-sm text-white/65">
                {{ formatNumber(total) }} active images available to explore
            </p>
        </div>
    </section>
</template>

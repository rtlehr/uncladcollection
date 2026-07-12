<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicSiteHeader from '@/components/Public/PublicSiteHeader.vue';
import PublicSiteFooter from '@/components/Public/PublicSiteFooter.vue';

const page = usePage();
const site = computed(() => (page.props.site ?? {}) as Record<string, any>);

const layoutStyle = computed(() => ({
    '--brand-primary': site.value.primary_color || '#1E2A38',
    '--brand-secondary': site.value.secondary_color || '#50634D',
    '--brand-accent': site.value.accent_color || '#D9824B',
}));
</script>

<template>
    <div
        :style="layoutStyle"
        class="min-h-screen bg-stone-50 text-stone-950 dark:bg-stone-950 dark:text-stone-50"
    >
        <a
            href="#main-content"
            class="fixed left-4 top-3 z-[100] -translate-y-20 rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white transition-transform focus:translate-y-0"
        >
            Skip to content
        </a>

        <PublicSiteHeader />

        <main id="main-content">
            <slot />
        </main>

        <PublicSiteFooter />
    </div>
</template>

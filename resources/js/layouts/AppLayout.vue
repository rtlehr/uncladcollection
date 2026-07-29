<script setup lang="ts">
import FlashMessage from '@/components/FlashMessage.vue';
import GlobalNavigationIndicator from '@/components/GlobalNavigationIndicator.vue';
import ImpersonationBanner from '@/components/Impersonation/ImpersonationBanner.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{ breadcrumbs?: BreadcrumbItem[] }>();
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <ImpersonationBanner />
        <GlobalNavigationIndicator />

        <a
            href="#main-content"
            class="fixed left-4 top-3 z-[110] -translate-y-20 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-lg transition-transform focus:translate-y-0"
        >
            Skip to content
        </a>

        <FlashMessage />

        <main id="main-content" tabindex="-1">
            <slot />
        </main>
    </AppSidebarLayout>
</template>

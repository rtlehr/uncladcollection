<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Download,
    ShoppingCart,
} from '@lucide/vue';

const props = defineProps<{
    canPurchase: boolean;
    canDownload: boolean;
    imageId: number;
}>();

function scrollToPurchase(): void {
    document
        .getElementById('purchase-panel')
        ?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
}
</script>

<template>
    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-stone-200 bg-white/95 p-3 backdrop-blur lg:hidden dark:border-stone-800 dark:bg-stone-950/95">
        <Link
            v-if="canDownload"
            :href="`/images/${imageId}/download`"
            class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white"
        >
            <Download class="h-4 w-4" />
            Download Image
        </Link>

        <button
            v-else-if="canPurchase"
            type="button"
            class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white"
            @click="scrollToPurchase"
        >
            <ShoppingCart class="h-4 w-4" />
            Choose a License
        </button>
    </div>
</template>

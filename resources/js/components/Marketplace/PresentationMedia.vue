<script setup lang="ts">
import { Images } from '@lucide/vue';
import { computed } from 'vue';

import PerformanceImage from '@/components/Public/PerformanceImage.vue';

const props = withDefaults(defineProps<{
    src?: string | null;
    alt: string;
    aspectClass?: string;
    imageClass?: string;
    loading?: 'eager' | 'lazy';
    fetchpriority?: 'high' | 'low' | 'auto';
    sizes?: string;
    showBackdrop?: boolean;
}>(), {
    src: null,
    aspectClass: 'aspect-[4/3]',
    imageClass: '',
    loading: 'lazy',
    fetchpriority: 'low',
    sizes: '100vw',
    showBackdrop: true,
});

const wrapperClasses = computed(() => [
    'relative isolate overflow-hidden bg-stone-100 dark:bg-stone-900',
    props.aspectClass,
]);
</script>

<template>
    <div :class="wrapperClasses">
        <template v-if="src">
            <div
                v-if="showBackdrop"
                class="absolute inset-0 -z-10 scale-110 bg-cover bg-center opacity-45 blur-2xl saturate-75 dark:opacity-30"
                :style="{ backgroundImage: `url(${src})` }"
                aria-hidden="true"
            />
            <div class="absolute inset-0 -z-10 bg-gradient-to-br from-white/35 via-transparent to-black/10 dark:from-white/5 dark:to-black/35" aria-hidden="true" />

            <PerformanceImage
                :src="src"
                :alt="alt"
                :loading="loading"
                :fetchpriority="fetchpriority"
                :sizes="sizes"
                wrapper-class="h-full w-full"
                :image-class="['h-full w-full object-cover', imageClass].join(' ')"
            />
        </template>

        <div v-else class="flex h-full w-full items-center justify-center" role="img" :aria-label="`${alt}: presentation image unavailable`">
            <Images class="h-10 w-10 text-stone-400" />
        </div>

        <slot />
    </div>
</template>

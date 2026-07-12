<script setup lang="ts">
import {
    computed,
    ref,
} from 'vue';

const props = withDefaults(defineProps<{
    src: string;
    alt: string;
    loading?: 'eager' | 'lazy';
    fetchpriority?: 'high' | 'low' | 'auto';
    decoding?: 'async' | 'sync' | 'auto';
    sizes?: string;
    width?: number | null;
    height?: number | null;
    imageClass?: string;
    wrapperClass?: string;
}>(), {
    loading: 'lazy',
    fetchpriority: 'auto',
    decoding: 'async',
    sizes: '100vw',
    width: null,
    height: null,
    imageClass: '',
    wrapperClass: '',
});

const loaded = ref(false);

const ratioStyle = computed(() => {
    if (!props.width || !props.height) {
        return undefined;
    }

    return {
        aspectRatio: `${props.width} / ${props.height}`,
    };
});
</script>

<template>
    <span
        :class="[
            'relative block overflow-hidden',
            !loaded ? 'public-media-placeholder' : '',
            wrapperClass,
        ]"
        :style="ratioStyle"
    >
        <img
            :src="src"
            :alt="alt"
            :loading="loading"
            :fetchpriority="fetchpriority"
            :decoding="decoding"
            :sizes="sizes"
            :width="width ?? undefined"
            :height="height ?? undefined"
            :class="[
                'h-full w-full transition-opacity duration-300',
                loaded ? 'opacity-100' : 'opacity-0',
                imageClass,
            ]"
            @load="loaded = true"
        />
    </span>
</template>

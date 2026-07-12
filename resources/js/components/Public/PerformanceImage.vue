<script setup lang="ts">
import {
    computed,
    nextTick,
    onMounted,
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
const imageElement = ref<HTMLImageElement | null>(null);

function markLoaded(): void {
    loaded.value = true;
}

onMounted(async () => {
    await nextTick();

    if (imageElement.value?.complete && imageElement.value.naturalWidth > 0) {
        markLoaded();
    }
});

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
            ref="imageElement"
            :src="src"
            :alt="alt"
            :loading="loading"
            :fetchpriority="fetchpriority"
            :decoding="decoding"
            :sizes="sizes"
            :width="width ?? undefined"
            :height="height ?? undefined"
            :class="[
                'h-full w-full transition-[opacity,transform,filter] duration-500 ease-[cubic-bezier(.2,.8,.2,1)]',
                loaded ? 'opacity-100' : 'opacity-0',
                imageClass,
            ]"
            @load="markLoaded"
        />
    </span>
</template>

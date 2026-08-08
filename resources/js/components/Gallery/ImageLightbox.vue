<script setup lang="ts">
import {
    ChevronLeft,
    ChevronRight,
    X,
    ZoomIn,
    ZoomOut,
} from '@lucide/vue';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

const props = defineProps<{
    open: boolean;
    imageUrl: string;
    title: string;
    previousHref?: string | null;
    nextHref?: string | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    previous: [];
    next: [];
}>();

const dialog = ref<HTMLElement | null>(null);
const zoomed = ref(false);
const touchStartX = ref<number | null>(null);

const imageClass = computed(() =>
    zoomed.value
        ? 'max-w-none cursor-zoom-out scale-150'
        : 'max-h-[calc(100dvh-7rem)] max-w-[96vw] cursor-zoom-in',
);

function close(): void {
    emit('update:open', false);
}

function handleKeydown(event: KeyboardEvent): void {
    if (!props.open) {
return;
}

    if (event.key === 'Escape') {
        event.preventDefault();
        close();
    }

    if (event.key === 'ArrowLeft' && props.previousHref) {
        emit('previous');
    }

    if (event.key === 'ArrowRight' && props.nextHref) {
        emit('next');
    }
}

function handleTouchStart(event: TouchEvent): void {
    touchStartX.value = event.touches[0]?.clientX ?? null;
}

function handleTouchEnd(event: TouchEvent): void {
    if (touchStartX.value === null) {
return;
}

    const endX = event.changedTouches[0]?.clientX ?? touchStartX.value;
    const distance = endX - touchStartX.value;

    touchStartX.value = null;

    if (Math.abs(distance) < 60) {
return;
}

    if (distance > 0 && props.previousHref) {
        emit('previous');
    }

    if (distance < 0 && props.nextHref) {
        emit('next');
    }
}

watch(
    () => props.open,
    async (open) => {
        document.body.style.overflow = open ? 'hidden' : '';

        if (open) {
            zoomed.value = false;
            await nextTick();
            dialog.value?.focus();
        }
    },
);

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            ref="dialog"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            :aria-label="`Expanded view of ${title}`"
            class="safe-top safe-bottom fixed inset-0 z-[120] flex items-center justify-center overflow-auto bg-black/95 px-2 outline-none sm:p-4"
            @click.self="close"
            @touchstart.passive="handleTouchStart"
            @touchend.passive="handleTouchEnd"
        >
            <div class="fixed inset-x-0 top-0 z-10 flex items-center justify-end gap-2 bg-gradient-to-b from-black/80 to-transparent p-3 safe-top">
                <button
                    type="button"
                    class="public-touch-target inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur"
                    :aria-label="zoomed ? 'Zoom out' : 'Zoom in'"
                    @click="zoomed = !zoomed"
                >
                    <ZoomOut v-if="zoomed" class="h-5 w-5" />
                    <ZoomIn v-else class="h-5 w-5" />
                </button>

                <button
                    type="button"
                    class="public-touch-target inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur"
                    aria-label="Close expanded image"
                    @click="close"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <button
                v-if="previousHref"
                type="button"
                class="public-touch-target fixed bottom-4 left-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur sm:bottom-auto sm:top-1/2 sm:-translate-y-1/2"
                aria-label="Previous image"
                @click="emit('previous')"
            >
                <ChevronLeft class="h-6 w-6" />
            </button>

            <button
                v-if="nextHref"
                type="button"
                class="public-touch-target fixed bottom-4 right-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur sm:bottom-auto sm:top-1/2 sm:-translate-y-1/2"
                aria-label="Next image"
                @click="emit('next')"
            >
                <ChevronRight class="h-6 w-6" />
            </button>

            <img
                :src="imageUrl"
                :alt="title"
                :class="[
                    imageClass,
                    'object-contain transition-transform duration-200',
                ]"
                @click="zoomed = !zoomed"
            />
        </div>
    </Teleport>
</template>

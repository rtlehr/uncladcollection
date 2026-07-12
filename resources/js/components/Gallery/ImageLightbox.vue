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

const imageClass = computed(() =>
    zoomed.value
        ? 'max-w-none cursor-zoom-out scale-150'
        : 'max-h-[88vh] max-w-[94vw] cursor-zoom-in',
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
            class="fixed inset-0 z-[120] flex items-center justify-center overflow-auto bg-black/95 p-4 outline-none"
            @click.self="close"
        >
            <button
                type="button"
                class="fixed right-4 top-4 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur transition hover:bg-white/20"
                aria-label="Close expanded image"
                @click="close"
            >
                <X class="h-5 w-5" />
            </button>

            <button
                type="button"
                class="fixed right-16 top-4 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur transition hover:bg-white/20"
                :aria-label="zoomed ? 'Zoom out' : 'Zoom in'"
                @click="zoomed = !zoomed"
            >
                <ZoomOut v-if="zoomed" class="h-5 w-5" />
                <ZoomIn v-else class="h-5 w-5" />
            </button>

            <button
                v-if="previousHref"
                type="button"
                class="fixed left-4 top-1/2 inline-flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur transition hover:bg-white/20"
                aria-label="Previous image"
                @click="emit('previous')"
            >
                <ChevronLeft class="h-6 w-6" />
            </button>

            <button
                v-if="nextHref"
                type="button"
                class="fixed right-4 top-1/2 inline-flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur transition hover:bg-white/20"
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

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Heart,
    Images,
} from '@lucide/vue';
import { computed, ref } from 'vue';

import type { GalleryImage } from '@/types/gallery';

const props = withDefaults(defineProps<{
    image: GalleryImage;
    showCollection?: boolean;
}>(), {
    showCollection: true,
});

const page = usePage();
const favoriteProcessing = ref(false);
const favoriteState = ref(props.image.is_favorited);
const favoriteCount = ref(props.image.favorites_count);

const isAuthenticated = computed(() =>
    Boolean((page.props.auth as any)?.user),
);

function toggleFavorite(): void {
    if (!isAuthenticated.value) {
        router.visit('/login');
        return;
    }

    if (favoriteProcessing.value) {
        return;
    }

    favoriteProcessing.value = true;
    const wasFavorited = favoriteState.value;

    favoriteState.value = !wasFavorited;
    favoriteCount.value = Math.max(
        0,
        favoriteCount.value + (wasFavorited ? -1 : 1),
    );

    const method = wasFavorited ? 'delete' : 'post';

    router[method](`/images/${props.image.id}/favorite`, {
        preserveScroll: true,
        preserveState: true,
        only: [],
        onError: () => {
            favoriteState.value = wasFavorited;
            favoriteCount.value = props.image.favorites_count;
        },
        onFinish: () => {
            favoriteProcessing.value = false;
        },
    } as any);
}
</script>

<template>
    <article class="group overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-stone-800 dark:bg-stone-900">
        <div class="relative overflow-hidden bg-stone-200 dark:bg-stone-800">
            <Link :href="`/images/${image.slug}`" class="block">
                <img
                    v-if="image.thumbnail_url || image.icon_url"
                    :src="image.thumbnail_url ?? image.icon_url ?? ''"
                    :alt="image.title"
                    loading="lazy"
                    decoding="async"
                    class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-105"
                />

                <div
                    v-else
                    class="flex aspect-[4/3] items-center justify-center"
                    role="img"
                    :aria-label="`${image.title}: preview unavailable`"
                >
                    <Images class="h-9 w-9 text-stone-400" />
                </div>

                <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-transparent opacity-70 transition group-hover:opacity-95" />

                <div class="absolute inset-x-0 bottom-0 hidden translate-y-2 p-4 text-white opacity-0 transition duration-300 sm:block sm:group-hover:translate-y-0 sm:group-hover:opacity-100">
                    <span class="inline-flex items-center gap-1 text-xs font-semibold">
                        View image
                        <ArrowUpRight class="h-3.5 w-3.5" />
                    </span>
                </div>
            </Link>

            <button
                type="button"
                class="absolute right-3 top-3 inline-flex h-10 w-10 items-center justify-center rounded-full bg-black/45 text-white backdrop-blur transition hover:bg-black/65 disabled:opacity-50"
                :aria-label="favoriteState ? 'Remove from favorites' : 'Add to favorites'"
                :aria-pressed="favoriteState"
                :disabled="favoriteProcessing"
                @click="toggleFavorite"
            >
                <Heart
                    :class="[
                        'h-5 w-5',
                        favoriteState ? 'fill-current' : '',
                    ]"
                />
            </button>

            <span
                v-if="image.is_ai_generated"
                class="absolute left-3 top-3 rounded-full bg-black/50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-white backdrop-blur"
            >
                AI Generated
            </span>
        </div>

        <div class="p-4 sm:p-5">
            <div
                v-if="showCollection && image.collection"
                class="mb-2 text-xs font-semibold uppercase tracking-wider text-[var(--brand-accent)]"
            >
                {{ image.collection.name }}
            </div>

            <Link
                :href="`/images/${image.slug}`"
                class="block line-clamp-2 text-base font-semibold leading-snug transition hover:text-[var(--brand-accent)] sm:text-lg"
            >
                {{ image.title }}
            </Link>

            <p class="mt-2 truncate text-sm text-stone-500 dark:text-stone-400">
                {{ image.photographer ? `By ${image.photographer}` : 'Unclad Collection' }}
            </p>

            <div class="mt-3 flex items-center justify-between border-t border-stone-100 pt-3 text-[11px] sm:mt-4 sm:pt-4 sm:text-xs text-stone-500 dark:border-stone-800 dark:text-stone-400">
                <span>{{ image.views_count.toLocaleString() }} views</span>
                <span class="inline-flex items-center gap-1">
                    <Heart class="h-3.5 w-3.5" />
                    {{ favoriteCount.toLocaleString() }}
                </span>
            </div>
        </div>
    </article>
</template>

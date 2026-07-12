<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Clock3 } from '@lucide/vue';
import {
    onMounted,
    ref,
} from 'vue';

type RecentImage = {
    id: number;
    title: string;
    slug: string;
    thumbnail_url: string | null;
    icon_url: string | null;
};

const props = defineProps<{
    currentImage: RecentImage;
}>();

const recentImages = ref<RecentImage[]>([]);

const storageKey = 'unclad-recently-viewed-images';

function load(): RecentImage[] {
    try {
        const value = window.localStorage.getItem(storageKey);

        if (!value) {
            return [];
        }

        const parsed = JSON.parse(value);

        return Array.isArray(parsed)
            ? parsed
            : [];
    } catch {
        return [];
    }
}

function save(images: RecentImage[]): void {
    window.localStorage.setItem(
        storageKey,
        JSON.stringify(images),
    );
}

onMounted(() => {
    const previous = load()
        .filter((image) => image.id !== props.currentImage.id);

    recentImages.value = previous.slice(0, 4);

    save([
        props.currentImage,
        ...previous,
    ].slice(0, 8));
});
</script>

<template>
    <section
        v-if="recentImages.length"
        class="border-t border-stone-200 bg-stone-50 py-14 dark:border-stone-800 dark:bg-stone-950"
    >
        <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
            <div class="flex items-center gap-3">
                <Clock3 class="h-5 w-5 text-[var(--brand-accent)]" />

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]">
                        Your browsing history
                    </p>

                    <h2 class="mt-1 text-2xl font-semibold">
                        Recently Viewed
                    </h2>
                </div>
            </div>

            <div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="image in recentImages"
                    :key="image.id"
                    :href="`/images/${image.slug}`"
                    class="group overflow-hidden rounded-2xl border border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900"
                >
                    <img
                        v-if="image.thumbnail_url || image.icon_url"
                        :src="image.thumbnail_url ?? image.icon_url ?? ''"
                        :alt="image.title"
                        loading="lazy"
                        class="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-105"
                    />

                    <div class="p-4 font-semibold">
                        {{ image.title }}
                    </div>
                </Link>
            </div>
        </div>
    </section>
</template>

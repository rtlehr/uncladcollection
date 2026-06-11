<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type Option = {
    id: number;
    name: string;
};

type ImageCard = {
    id: number;
    title: string;
    slug: string;
    thumbnail_url: string | null;
    is_ai_generated: boolean;
    favorites_count: number;
    views_count: number;
    collection: Option | null;
    categories: Option[];
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedImages = {
    data: ImageCard[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

defineProps<{
    images: PaginatedImages;
}>();

function formatNumber(value: number): string {
    return Number(value ?? 0).toLocaleString();
}
</script>

<template>
    <Head title="My Favorites" />

    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-3xl font-semibold">My Favorites</h1>

            <p class="text-sm text-muted-foreground">
                Images you have saved to your favorites.
            </p>
        </div>

        <div
            v-if="images.data.length"
            class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <Link
                v-for="image in images.data"
                :key="image.id"
                :href="`/images/${image.slug}`"
                class="group overflow-hidden rounded-lg border bg-card shadow-sm transition hover:shadow-md"
            >
                <div class="aspect-square bg-muted">
                    <img
                        v-if="image.thumbnail_url"
                        :src="image.thumbnail_url"
                        :alt="image.title"
                        class="h-full w-full object-cover transition group-hover:scale-105"
                    />

                    <div
                        v-else
                        class="flex h-full items-center justify-center text-sm text-muted-foreground"
                    >
                        No preview
                    </div>
                </div>

                <div class="space-y-3 p-4">
                    <div>
                        <h2 class="line-clamp-1 font-semibold">
                            {{ image.title }}
                        </h2>

                        <p class="line-clamp-1 text-xs text-muted-foreground">
                            {{ image.collection?.name ?? 'Unassigned' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span
                            v-if="image.is_ai_generated"
                            class="rounded-full border px-2 py-0.5 text-xs"
                        >
                            AI
                        </span>

                        <span
                            v-for="category in image.categories.slice(0, 2)"
                            :key="category.id"
                            class="rounded-full border px-2 py-0.5 text-xs"
                        >
                            {{ category.name }}
                        </span>
                    </div>

                    <div class="flex justify-between text-xs text-muted-foreground">
                        <span>{{ formatNumber(image.views_count) }} views</span>
                        <span>{{ formatNumber(image.favorites_count) }} favorites</span>
                    </div>
                </div>
            </Link>
        </div>

        <div
            v-else
            class="rounded-lg border bg-card p-12 text-center"
        >
            <h2 class="text-lg font-semibold">No favorites yet</h2>

            <p class="mt-2 text-sm text-muted-foreground">
                Browse the image library and click the heart icon to save images here.
            </p>

            <Link
                href="/images"
                class="mt-4 inline-flex rounded-md border px-4 py-2 text-sm font-medium"
            >
                Browse Images
            </Link>
        </div>

        <div
            v-if="images.links.length > 3"
            class="flex flex-wrap justify-center gap-2"
        >
            <Link
                v-for="link in images.links"
                :key="link.label"
                :href="link.url ?? '#'"
                preserve-scroll
                class="rounded-md border px-3 py-2 text-sm"
                :class="[
                    link.active ? 'bg-primary text-primary-foreground' : '',
                    !link.url ? 'pointer-events-none opacity-50' : '',
                ]"
                v-html="link.label"
            />
        </div>
    </div>
</template>
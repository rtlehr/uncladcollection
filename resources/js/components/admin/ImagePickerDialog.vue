<script setup lang="ts">
import { ref, watch } from 'vue';
import { Images, Search, X } from '@lucide/vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export interface LibraryImage {
    id: number;
    title: string;
    slug: string;
    photographer: string | null;
    thumbnail_url: string | null;
    icon_url: string | null;
    high_res_url: string | null;
    public_url: string | null;
}

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    close: [];
    select: [image: LibraryImage];
}>();

const search = ref('');
const images = ref<LibraryImage[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen) {
            await searchImages();
        }
    }
);

async function searchImages() {
    loading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();

        if (search.value.trim()) {
            params.append('search', search.value.trim());
        }

        const response = await fetch(`/admin/blog-posts/image-library?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            const data = await response.json().catch(() => null);
            error.value =
                data?.message
                ?? `Unable to load image library (${response.status}).`;
            return;
        }

        const data = await response.json();

        images.value = data.images ?? [];
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load image library.';
    } finally {
        loading.value = false;
    }
}

function close() {
    emit('close');
}

function selectImage(image: LibraryImage) {
    emit('select', image);
}
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
        <div class="max-h-[85vh] w-full max-w-5xl overflow-hidden rounded-lg border bg-background shadow-lg">
            <div class="flex items-center justify-between border-b p-4">
                <div>
                    <h3 class="flex items-center gap-2 text-lg font-semibold">
                        <Images class="h-5 w-5" />
                        Choose From Asset Library
                    </h3>

                    <p class="text-sm text-muted-foreground">
                        Choose an Asset image, then crop it before inserting it into the article.
                    </p>
                </div>

                <Button type="button" size="icon" variant="ghost" @click="close">
                    <X class="h-4 w-4" />
                </Button>
            </div>

            <div class="border-b p-4">
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />

                        <Input
                            v-model="search"
                            class="pl-9"
                            placeholder="Search by title, slug, or photographer..."
                            @keyup.enter="searchImages"
                        />
                    </div>

                    <Button type="button" @click="searchImages">
                        Search
                    </Button>
                </div>
            </div>

            <div class="max-h-[58vh] overflow-y-auto p-4">
                <div
                    v-if="error"
                    class="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
                >
                    {{ error }}
                </div>

                <div v-else-if="loading" class="py-12 text-center text-sm text-muted-foreground">
                    Loading assets...
                </div>

                <div v-else-if="images.length === 0" class="py-12 text-center text-sm text-muted-foreground">
                    No previewable Asset images found.
                </div>

                <div v-else class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                    <button
                        v-for="image in images"
                        :key="image.id"
                        type="button"
                        class="overflow-hidden rounded-md border bg-card text-left shadow-sm transition hover:border-primary hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        title="Select and edit this Asset image"
                        @click="selectImage(image)"
                    >
                        <div class="aspect-[4/3] bg-muted">
                            <img
                                v-if="image.thumbnail_url || image.icon_url"
                                :src="image.thumbnail_url ?? image.icon_url ?? ''"
                                :alt="image.title"
                                class="h-full w-full object-cover"
                            />

                            <div
                                v-else
                                class="flex h-full items-center justify-center text-xs text-muted-foreground"
                            >
                                No preview
                            </div>
                        </div>

                        <div class="space-y-1 p-3">
                            <div class="line-clamp-2 text-sm font-medium">
                                {{ image.title }}
                            </div>

                            <div class="text-xs text-muted-foreground">
                                {{ image.photographer ?? 'Unknown photographer' }}
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
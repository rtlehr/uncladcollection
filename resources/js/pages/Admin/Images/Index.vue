<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Collection = {
    id: number;
    name: string;
};

type ImageRecord = {
    id: number;
    title: string;
    slug: string;
    thumbnail_url: string | null;
    photographer: string | null;
    sort_order: number;
    is_active: boolean;
    collection: Collection | null;
};

const props = defineProps<{
    images: ImageRecord[];
    collections: Collection[];
    filters: {
        search: string;
        status: string;
        collection_id: string;
        sort: string;
        direction: string;
    };
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const collectionId = ref(props.filters.collection_id ?? '');

function reload() {
    router.get('/admin/images', {
        search: search.value,
        status: status.value,
        collection_id: collectionId.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    status.value = '';
    collectionId.value = '';

    router.get('/admin/images', {}, {
        preserveState: true,
        replace: true,
    });
}

function sortBy(column: string) {
    const direction =
        props.filters.sort === column && props.filters.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get('/admin/images', {
        search: search.value,
        status: status.value,
        collection_id: collectionId.value,
        sort: column,
        direction,
    }, {
        preserveState: true,
        replace: true,
    });
}

function sortIndicator(column: string) {
    if (props.filters.sort !== column) {
        return '↕';
    }

    return props.filters.direction === 'asc'
        ? '↑'
        : '↓';
}

function deleteImage(image: ImageRecord) {
    if (!confirm(`Delete image "${image.title}"?`)) {
        return;
    }

    router.delete(`/admin/images/${image.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Images" />

    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Images</h1>

                <p class="text-sm text-muted-foreground">
                    Manage image uploads and collections.
                </p>
            </div>

            <Button as-child>
                <Link href="/admin/images/create">
                    Add Image
                </Link>
            </Button>
        </div>

        <div class="mb-4 flex flex-wrap gap-3">
            <Input
                v-model="search"
                class="max-w-sm"
                placeholder="Search title, slug, photographer..."
                @keyup.enter="reload"
            />

            <select
                v-model="collectionId"
                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                @change="reload"
            >
                <option value="">
                    All Collections
                </option>

                <option
                    v-for="collection in collections"
                    :key="collection.id"
                    :value="collection.id"
                >
                    {{ collection.name }}
                </option>
            </select>

            <select
                v-model="status"
                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                @change="reload"
            >
                <option value="">
                    All Statuses
                </option>

                <option value="1">
                    Active
                </option>

                <option value="0">
                    Inactive
                </option>
            </select>

            <Button @click="reload">
                Search
            </Button>

            <Button
                variant="outline"
                @click="resetFilters"
            >
                Reset
            </Button>
        </div>

        <div class="overflow-hidden rounded-lg border bg-card shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="p-4">
                            Preview
                        </th>

                        <th
                            class="cursor-pointer p-4"
                            @click="sortBy('title')"
                        >
                            Title {{ sortIndicator('title') }}
                        </th>

                        <th class="p-4">
                            Collection
                        </th>

                        <th
                            class="cursor-pointer p-4"
                            @click="sortBy('photographer')"
                        >
                            Photographer {{ sortIndicator('photographer') }}
                        </th>

                        <th
                            class="cursor-pointer p-4"
                            @click="sortBy('sort_order')"
                        >
                            Sort {{ sortIndicator('sort_order') }}
                        </th>

                        <th
                            class="cursor-pointer p-4"
                            @click="sortBy('is_active')"
                        >
                            Status {{ sortIndicator('is_active') }}
                        </th>

                        <th class="p-4 text-right">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="image in images"
                        :key="image.id"
                        class="border-b last:border-0"
                    >
                        <td class="p-4">
                            <img
                                v-if="image.thumbnail_url"
                                :src="image.thumbnail_url"
                                :alt="image.title"
                                class="h-16 w-16 rounded object-cover border"
                            />

                            <div
                                v-else
                                class="flex h-16 w-16 items-center justify-center rounded border text-xs text-muted-foreground"
                            >
                                No Image
                            </div>
                        </td>

                        <td class="p-4">
                            <div class="font-medium">
                                {{ image.title }}
                            </div>

                            <div class="font-mono text-xs text-muted-foreground">
                                {{ image.slug }}
                            </div>
                        </td>

                        <td class="p-4">
                            {{ image.collection?.name ?? '—' }}
                        </td>

                        <td class="p-4">
                            {{ image.photographer ?? '—' }}
                        </td>

                        <td class="p-4">
                            {{ image.sort_order }}
                        </td>

                        <td class="p-4">
                            <span
                                :class="image.is_active
                                    ? 'font-medium text-green-600'
                                    : 'font-medium text-red-600'"
                            >
                                {{ image.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>

                        <td class="p-4">
                            <div class="flex justify-end gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    as-child
                                >
                                    <Link :href="`/admin/images/${image.id}`">
                                        View
                                    </Link>
                                </Button>

                                <Button
                                    size="sm"
                                    variant="outline"
                                    as-child
                                >
                                    <Link :href="`/admin/images/${image.id}/edit`">
                                        Edit
                                    </Link>
                                </Button>

                                <Button
                                    size="sm"
                                    variant="destructive"
                                    @click="deleteImage(image)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="images.length === 0">
                        <td
                            colspan="7"
                            class="p-6 text-center text-muted-foreground"
                        >
                            No images found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
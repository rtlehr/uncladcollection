<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import ActivityLog from '@/components/admin/ActivityLog.vue';

type Collection = {
    id: number;
    name: string;
};

type Option = {
    id: number;
    name: string;
};

type ImageRecord = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    original_url: string | null;
    high_res_url: string | null;
    thumbnail_url: string | null;
    icon_url: string | null;
    photographer: string | null;
    sort_order: number;
    is_active: boolean;
    is_ai_generated: boolean;
    downloads_count: number;
    favorites_count: number;
    purchases_count: number;
    views_count: number;
    collection: Collection | null;
    categories: Option[];
    tags: Option[];
    created_at: string | null;
    updated_at: string | null;
};

type Activity = {
    id: number;
    admin_name: string;
    action: string;
    field_name: string | null;
    old_value: string | null;
    new_value: string | null;
    description: string | null;
    created_at: string | null;
};

defineProps<{
    imageRecord: ImageRecord;
    activities: Activity[];
}>();

function formatNumber(value: number | null | undefined): string {
    return Number(value ?? 0).toLocaleString();
}
</script>

<template>
    <Head :title="`Image: ${imageRecord.title}`" />

    <div class="space-y-6 p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Image Details</h1>

                <p class="text-sm text-muted-foreground">
                    View image details, file links, metadata, statistics, and change history.
                </p>
            </div>

            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link href="/admin/images">
                        Back
                    </Link>
                </Button>

                <Button as-child>
                    <Link :href="`/admin/images/${imageRecord.id}/edit`">
                        Edit Image
                    </Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">Image Preview</h2>

                    <div
                        v-if="imageRecord.thumbnail_url || imageRecord.original_url || imageRecord.high_res_url"
                        class="rounded-lg border bg-muted p-4"
                    >
                        <img
                            :src="imageRecord.thumbnail_url || imageRecord.original_url || imageRecord.high_res_url || ''"
                            :alt="imageRecord.title"
                            class="max-h-[600px] w-full rounded object-contain"
                        />
                    </div>

                    <div
                        v-else
                        class="flex h-64 items-center justify-center rounded-lg border text-sm text-muted-foreground"
                    >
                        No image preview available.
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <Button
                            v-if="imageRecord.original_url"
                            variant="outline"
                            size="sm"
                            as-child
                        >
                            <a :href="imageRecord.original_url" target="_blank">
                                Open Original
                            </a>
                        </Button>

                        <Button
                            v-if="imageRecord.high_res_url"
                            variant="outline"
                            size="sm"
                            as-child
                        >
                            <a :href="imageRecord.high_res_url" target="_blank">
                                Open High Res
                            </a>
                        </Button>

                        <Button
                            v-if="imageRecord.thumbnail_url"
                            variant="outline"
                            size="sm"
                            as-child
                        >
                            <a :href="imageRecord.thumbnail_url" target="_blank">
                                Open Thumbnail
                            </a>
                        </Button>

                        <Button
                            v-if="imageRecord.icon_url"
                            variant="outline"
                            size="sm"
                            as-child
                        >
                            <a :href="imageRecord.icon_url" target="_blank">
                                Open Icon
                            </a>
                        </Button>
                    </div>
                </div>

                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">Image Information</h2>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Title
                            </div>

                            <div class="mt-1">
                                {{ imageRecord.title }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Slug
                            </div>

                            <div class="mt-1 font-mono text-sm">
                                {{ imageRecord.slug }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Photographer
                            </div>

                            <div class="mt-1">
                                {{ imageRecord.photographer || '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                AI Generated
                            </div>

                            <div class="mt-1">
                                {{ imageRecord.is_ai_generated ? 'Yes' : 'No' }}
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <div class="text-sm font-medium text-muted-foreground">
                                Description
                            </div>

                            <div class="mt-1 whitespace-pre-line">
                                {{ imageRecord.description || '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-lg border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold">Categories</h2>

                        <div v-if="imageRecord.categories.length" class="flex flex-wrap gap-2">
                            <span
                                v-for="category in imageRecord.categories"
                                :key="category.id"
                                class="rounded-md border px-3 py-1 text-sm"
                            >
                                {{ category.name }}
                            </span>
                        </div>

                        <p v-else class="text-sm text-muted-foreground">
                            No categories assigned.
                        </p>
                    </div>

                    <div class="rounded-lg border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold">Tags</h2>

                        <div v-if="imageRecord.tags.length" class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in imageRecord.tags"
                                :key="tag.id"
                                class="rounded-md border px-3 py-1 text-sm"
                            >
                                {{ tag.name }}
                            </span>
                        </div>

                        <p v-else class="text-sm text-muted-foreground">
                            No tags assigned.
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">Publishing</h2>

                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Status
                            </div>

                            <div class="mt-1">
                                <span
                                    :class="imageRecord.is_active
                                        ? 'font-medium text-green-600'
                                        : 'font-medium text-red-600'"
                                >
                                    {{ imageRecord.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Collection
                            </div>

                            <div class="mt-1">
                                {{ imageRecord.collection?.name ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Sort Order
                            </div>

                            <div class="mt-1">
                                {{ imageRecord.sort_order }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">Statistics</h2>

                    <div class="grid gap-4">
                        <div class="rounded-md border p-4">
                            <div class="text-sm font-medium text-muted-foreground">
                                Downloads
                            </div>

                            <div class="mt-1 text-2xl font-semibold">
                                {{ formatNumber(imageRecord.downloads_count) }}
                            </div>
                        </div>

                        <div class="rounded-md border p-4">
                            <div class="text-sm font-medium text-muted-foreground">
                                Favorites
                            </div>

                            <div class="mt-1 text-2xl font-semibold">
                                {{ formatNumber(imageRecord.favorites_count) }}
                            </div>
                        </div>

                        <div class="rounded-md border p-4">
                            <div class="text-sm font-medium text-muted-foreground">
                                Purchases
                            </div>

                            <div class="mt-1 text-2xl font-semibold">
                                {{ formatNumber(imageRecord.purchases_count) }}
                            </div>
                        </div>

                        <div class="rounded-md border p-4">
                            <div class="text-sm font-medium text-muted-foreground">
                                Views
                            </div>

                            <div class="mt-1 text-2xl font-semibold">
                                {{ formatNumber(imageRecord.views_count) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">File Status</h2>

                    <div class="grid gap-4">
                        <div class="rounded-md border p-4">
                            <div class="text-sm font-medium text-muted-foreground">
                                Original
                            </div>

                            <div class="mt-1 font-medium">
                                {{ imageRecord.original_url ? 'Available' : 'Missing' }}
                            </div>
                        </div>

                        <div class="rounded-md border p-4">
                            <div class="text-sm font-medium text-muted-foreground">
                                High Res
                            </div>

                            <div class="mt-1 font-medium">
                                {{ imageRecord.high_res_url ? 'Available' : 'Missing' }}
                            </div>
                        </div>

                        <div class="rounded-md border p-4">
                            <div class="text-sm font-medium text-muted-foreground">
                                Thumbnail
                            </div>

                            <div class="mt-1 font-medium">
                                {{ imageRecord.thumbnail_url ? 'Available' : 'Missing' }}
                            </div>
                        </div>

                        <div class="rounded-md border p-4">
                            <div class="text-sm font-medium text-muted-foreground">
                                Icon
                            </div>

                            <div class="mt-1 font-medium">
                                {{ imageRecord.icon_url ? 'Available' : 'Missing' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">Metadata</h2>

                    <div class="space-y-4">
                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Created
                            </div>

                            <div class="mt-1">
                                {{ imageRecord.created_at || '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Last Updated
                            </div>

                            <div class="mt-1">
                                {{ imageRecord.updated_at || '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Image ID
                            </div>

                            <div class="mt-1 font-mono text-sm">
                                {{ imageRecord.id }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ActivityLog :activities="activities" />
    </div>
</template>
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
    collection: Collection | null;
    categories?: Option[];
    tags?: Option[];
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
</script>

<template>
    <Head :title="`Image: ${imageRecord.title}`" />

    <div class="p-6 space-y-6">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Image Details</h1>
                <p class="text-sm text-muted-foreground">
                    View image information and change history.
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
            <div class="lg:col-span-2 rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">
                    Preview
                </h2>

                <div
                    v-if="imageRecord.thumbnail_url || imageRecord.original_url"
                    class="rounded-lg border bg-muted p-4"
                >
                    <img
                        :src="imageRecord.thumbnail_url || imageRecord.original_url || ''"
                        :alt="imageRecord.title"
                        class="max-h-[500px] w-full object-contain"
                    />
                </div>

                <div
                    v-else
                    class="flex h-64 items-center justify-center rounded-lg border text-sm text-muted-foreground"
                >
                    No preview available
                </div>
            </div>

            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">
                    Publishing
                </h2>

                <div class="space-y-4">
                    <div>
                        <div class="text-sm text-muted-foreground">
                            Status
                        </div>

                        <div
                            :class="imageRecord.is_active
                                ? 'font-medium text-green-600'
                                : 'font-medium text-red-600'"
                        >
                            {{ imageRecord.is_active ? 'Active' : 'Inactive' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-muted-foreground">
                            Collection
                        </div>

                        <div>
                            {{ imageRecord.collection?.name ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-muted-foreground">
                            Sort Order
                        </div>

                        <div>
                            {{ imageRecord.sort_order }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-lg border bg-card p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">
                Image Information
            </h2>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <div class="text-sm text-muted-foreground">
                        Title
                    </div>

                    <div>
                        {{ imageRecord.title }}
                    </div>
                </div>

                <div>
                    <div class="text-sm text-muted-foreground">
                        Slug
                    </div>

                    <div class="font-mono text-sm">
                        {{ imageRecord.slug }}
                    </div>
                </div>

                <div>
                    <div class="text-sm text-muted-foreground">
                        Photographer
                    </div>

                    <div>
                        {{ imageRecord.photographer || '—' }}
                    </div>
                </div>

                <div class="md:col-span-2">
                    <div class="text-sm text-muted-foreground">
                        Description
                    </div>

                    <div class="whitespace-pre-line">
                        {{ imageRecord.description || '—' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-lg border bg-card p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">
                File Status
            </h2>

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-md border p-4">
                    <div class="text-sm text-muted-foreground">
                        Original
                    </div>

                    <div class="font-medium">
                        {{ imageRecord.original_url ? 'Available' : 'Missing' }}
                    </div>
                </div>

                <div class="rounded-md border p-4">
                    <div class="text-sm text-muted-foreground">
                        High Resolution
                    </div>

                    <div class="font-medium">
                        {{ imageRecord.high_res_url ? 'Available' : 'Missing' }}
                    </div>
                </div>

                <div class="rounded-md border p-4">
                    <div class="text-sm text-muted-foreground">
                        Thumbnail
                    </div>

                    <div class="font-medium">
                        {{ imageRecord.thumbnail_url ? 'Available' : 'Missing' }}
                    </div>
                </div>

                <div class="rounded-md border p-4">
                    <div class="text-sm text-muted-foreground">
                        Icon
                    </div>

                    <div class="font-medium">
                        {{ imageRecord.icon_url ? 'Available' : 'Missing' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">
                    Categories
                </h2>

                <div
                    v-if="imageRecord.categories?.length"
                    class="flex flex-wrap gap-2"
                >
                    <span
                        v-for="category in imageRecord.categories"
                        :key="category.id"
                        class="rounded-md border px-3 py-1 text-sm"
                    >
                        {{ category.name }}
                    </span>
                </div>

                <p
                    v-else
                    class="text-sm text-muted-foreground"
                >
                    No categories assigned.
                </p>
            </div>

            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">
                    Tags
                </h2>

                <div
                    v-if="imageRecord.tags?.length"
                    class="flex flex-wrap gap-2"
                >
                    <span
                        v-for="tag in imageRecord.tags"
                        :key="tag.id"
                        class="rounded-md border px-3 py-1 text-sm"
                    >
                        {{ tag.name }}
                    </span>
                </div>

                <p
                    v-else
                    class="text-sm text-muted-foreground"
                >
                    No tags assigned.
                </p>
            </div>
        </div>

        <ActivityLog :activities="activities" />
        
    </div>
</template>
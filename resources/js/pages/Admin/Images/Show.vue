<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Download,
    Eye,
    Heart,
    ShoppingBag,
} from '@lucide/vue';

import ActivityLog from '@/components/admin/ActivityLog.vue';
import ShowDetailsGrid from '@/Components/Show/ShowDetailsGrid.vue';
import ShowPageHeader from '@/Components/Show/ShowPageHeader.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import ChipList from '@/Components/Shared/ChipList.vue';
import DetailRow from '@/Components/Shared/DetailRow.vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { Button } from '@/components/ui/button';

import type {
    AdminImageActivity,
    AdminImageDetail,
} from '@/types/adminImageDetail';

const props = defineProps<{
    imageRecord: AdminImageDetail;
    activities: AdminImageActivity[];
}>();

function formatNumber(value: number | null | undefined): string {
    return Number(value ?? 0).toLocaleString();
}

const previewUrl =
    props.imageRecord.thumbnail_url
    || props.imageRecord.original_url
    || props.imageRecord.high_res_url
    || props.imageRecord.icon_url;
</script>

<template>
    <Head :title="`Image: ${imageRecord.title}`" />

    <div class="space-y-6 p-6">
        <ShowPageHeader
            title="Image Details"
            description="View image files, metadata, publishing status, statistics, and change history."
            eyebrow="Marketplace"
        >
            <template #actions>
                <StatusBadge
                    :status="imageRecord.is_active ? 'active' : 'inactive'"
                    size="md"
                />

                <StatusBadge
                    v-if="imageRecord.is_ai_generated"
                    status="ai_generated"
                    size="md"
                />

                <Button
                    variant="outline"
                    as-child
                >
                    <Link href="/admin/images">
                        Back
                    </Link>
                </Button>

                <Button as-child>
                    <Link :href="`/admin/images/${imageRecord.id}/edit`">
                        Edit Image
                    </Link>
                </Button>
            </template>
        </ShowPageHeader>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
            <div class="space-y-6">
                <ShowSection
                    title="Image Preview"
                    description="Preview the best available generated image file."
                >
                    <div
                        v-if="previewUrl"
                        class="rounded-lg border bg-muted p-4"
                    >
                        <img
                            :src="previewUrl"
                            :alt="imageRecord.title"
                            class="max-h-[650px] w-full rounded-md object-contain"
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
                            <a
                                :href="imageRecord.original_url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Open Original
                            </a>
                        </Button>

                        <Button
                            v-if="imageRecord.high_res_url"
                            variant="outline"
                            size="sm"
                            as-child
                        >
                            <a
                                :href="imageRecord.high_res_url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Open High Res
                            </a>
                        </Button>

                        <Button
                            v-if="imageRecord.thumbnail_url"
                            variant="outline"
                            size="sm"
                            as-child
                        >
                            <a
                                :href="imageRecord.thumbnail_url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Open Thumbnail
                            </a>
                        </Button>

                        <Button
                            v-if="imageRecord.icon_url"
                            variant="outline"
                            size="sm"
                            as-child
                        >
                            <a
                                :href="imageRecord.icon_url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Open Icon
                            </a>
                        </Button>
                    </div>
                </ShowSection>

                <ShowSection
                    title="Image Information"
                    description="Public-facing image title, attribution, and description."
                >
                    <ShowDetailsGrid :columns="2">
                        <DetailRow
                            label="Title"
                            :value="imageRecord.title"
                        />

                        <DetailRow
                            label="Slug"
                            :value="imageRecord.slug"
                            break-all
                        />

                        <DetailRow
                            label="Photographer"
                            :value="imageRecord.photographer"
                        />

                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                AI Generated
                            </div>

                            <div class="mt-1">
                                <StatusBadge
                                    :status="
                                        imageRecord.is_ai_generated
                                            ? 'ai_generated'
                                            : 'non_ai'
                                    "
                                    :label="
                                        imageRecord.is_ai_generated
                                            ? 'Yes'
                                            : 'No'
                                    "
                                    :tone="
                                        imageRecord.is_ai_generated
                                            ? 'info'
                                            : 'neutral'
                                    "
                                />
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <div class="text-sm font-medium text-muted-foreground">
                                Description
                            </div>

                            <div class="mt-1 whitespace-pre-line leading-7">
                                {{ imageRecord.description || '—' }}
                            </div>
                        </div>
                    </ShowDetailsGrid>
                </ShowSection>

                <div class="grid gap-6 lg:grid-cols-2">
                    <ShowSection
                        title="Categories"
                        description="Categories assigned to this image."
                    >
                        <ChipList
                            v-if="imageRecord.categories.length"
                            :items="imageRecord.categories"
                        />

                        <p
                            v-else
                            class="text-sm text-muted-foreground"
                        >
                            No categories assigned.
                        </p>
                    </ShowSection>

                    <ShowSection
                        title="Tags"
                        description="Search and discovery tags assigned to this image."
                    >
                        <ChipList
                            v-if="imageRecord.tags.length"
                            :items="imageRecord.tags"
                        />

                        <p
                            v-else
                            class="text-sm text-muted-foreground"
                        >
                            No tags assigned.
                        </p>
                    </ShowSection>
                </div>
            </div>

            <div class="space-y-6">
                <ShowSection
                    title="Publishing"
                    description="Marketplace visibility and organization."
                >
                    <ShowDetailsGrid :columns="1">
                        <div>
                            <div class="text-sm font-medium text-muted-foreground">
                                Status
                            </div>

                            <div class="mt-1">
                                <StatusBadge
                                    :status="imageRecord.is_active ? 'active' : 'inactive'"
                                />
                            </div>
                        </div>

                        <DetailRow
                            label="Collection"
                            :value="imageRecord.collection?.name"
                        />

                        <DetailRow
                            label="Sort Order"
                            :value="imageRecord.sort_order"
                        />
                    </ShowDetailsGrid>
                </ShowSection>

                <ShowSection
                    title="Statistics"
                    description="Marketplace engagement and purchase activity."
                >
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <MetricCard
                            label="Downloads"
                            :value="formatNumber(imageRecord.downloads_count)"
                            size="sm"
                        >
                            <template #icon>
                                <Download class="h-5 w-5" />
                            </template>
                        </MetricCard>

                        <MetricCard
                            label="Favorites"
                            :value="formatNumber(imageRecord.favorites_count)"
                            size="sm"
                        >
                            <template #icon>
                                <Heart class="h-5 w-5" />
                            </template>
                        </MetricCard>

                        <MetricCard
                            label="Purchases"
                            :value="formatNumber(imageRecord.purchases_count)"
                            size="sm"
                        >
                            <template #icon>
                                <ShoppingBag class="h-5 w-5" />
                            </template>
                        </MetricCard>

                        <MetricCard
                            label="Views"
                            :value="formatNumber(imageRecord.views_count)"
                            size="sm"
                        >
                            <template #icon>
                                <Eye class="h-5 w-5" />
                            </template>
                        </MetricCard>
                    </div>
                </ShowSection>

                <ShowSection
                    title="File Status"
                    description="Availability of generated image variants."
                >
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-4 rounded-md border p-3">
                            <span class="text-sm font-medium">Original</span>
                            <StatusBadge
                                :status="imageRecord.original_url ? 'available' : 'missing'"
                                :label="imageRecord.original_url ? 'Available' : 'Missing'"
                                :tone="imageRecord.original_url ? 'success' : 'danger'"
                            />
                        </div>

                        <div class="flex items-center justify-between gap-4 rounded-md border p-3">
                            <span class="text-sm font-medium">High Res</span>
                            <StatusBadge
                                :status="imageRecord.high_res_url ? 'available' : 'missing'"
                                :label="imageRecord.high_res_url ? 'Available' : 'Missing'"
                                :tone="imageRecord.high_res_url ? 'success' : 'danger'"
                            />
                        </div>

                        <div class="flex items-center justify-between gap-4 rounded-md border p-3">
                            <span class="text-sm font-medium">Thumbnail</span>
                            <StatusBadge
                                :status="imageRecord.thumbnail_url ? 'available' : 'missing'"
                                :label="imageRecord.thumbnail_url ? 'Available' : 'Missing'"
                                :tone="imageRecord.thumbnail_url ? 'success' : 'danger'"
                            />
                        </div>

                        <div class="flex items-center justify-between gap-4 rounded-md border p-3">
                            <span class="text-sm font-medium">Icon</span>
                            <StatusBadge
                                :status="imageRecord.icon_url ? 'available' : 'missing'"
                                :label="imageRecord.icon_url ? 'Available' : 'Missing'"
                                :tone="imageRecord.icon_url ? 'success' : 'danger'"
                            />
                        </div>
                    </div>
                </ShowSection>

                <ShowSection
                    title="Metadata"
                    description="Administrative timestamps and identifiers."
                >
                    <ShowDetailsGrid :columns="1">
                        <DetailRow
                            label="Created"
                            :value="imageRecord.created_at"
                        />

                        <DetailRow
                            label="Last Updated"
                            :value="imageRecord.updated_at"
                        />

                        <DetailRow
                            label="Image ID"
                            :value="imageRecord.id"
                        />
                    </ShowDetailsGrid>
                </ShowSection>
            </div>
        </div>

        <ActivityLog :activities="activities" />
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Eye, MessageSquare, Star } from '@lucide/vue';

import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import MetricCard from '@/Components/Shared/MetricCard.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DetailRow from '@/Components/Shared/DetailRow.vue';
import ShowDetailsGrid from '@/Components/Show/ShowDetailsGrid.vue';
import ShowPageHeader from '@/Components/Show/ShowPageHeader.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import { Button } from '@/components/ui/button';

import type {
    AdminBlogActivity,
    AdminBlogPostDetail,
} from '@/types/adminBlog';

const props = defineProps<{
    blogPost: AdminBlogPostDetail;
    activity?: AdminBlogActivity[];
}>();

const activityItems = computed(() => props.activity ?? []);
const deleteDialogOpen = ref(false);
const deleting = ref(false);

function formatDate(value: string | null | undefined): string {
    return value ? new Date(value).toLocaleString() : '—';
}

function confirmDelete() {
    deleting.value = true;

    router.delete(`/admin/blog-posts/${props.blogPost.slug}`, {
        onFinish: () => {
            deleting.value = false;
            deleteDialogOpen.value = false;
        },
    });
}
</script>

<template>
    <Head :title="blogPost.title" />

    <AppLayout>
        <div class="space-y-6 p-6">
            <ShowPageHeader
                title="Blog Post Preview"
                description="Preview the article and review its administrative details."
                eyebrow="Content"
            >
                <template #actions>
                    <StatusBadge :status="blogPost.status" size="md" />
                    <StatusBadge
                        :status="blogPost.is_active ? 'active' : 'inactive'"
                        size="md"
                    />

                    <Button variant="outline" as-child>
                        <Link href="/admin/blog-posts">Back</Link>
                    </Button>

                    <Button as-child>
                        <Link :href="`/admin/blog-posts/${blogPost.slug}/edit`">
                            Edit
                        </Link>
                    </Button>

                    <Button
                        variant="destructive"
                        @click="deleteDialogOpen = true"
                    >
                        Delete
                    </Button>
                </template>
            </ShowPageHeader>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <MetricCard
                    label="Views"
                    :value="blogPost.views_count.toLocaleString()"
                >
                    <template #icon>
                        <Eye class="h-5 w-5" />
                    </template>
                </MetricCard>

                <MetricCard
                    label="Featured"
                    :value="blogPost.is_featured ? 'Yes' : 'No'"
                >
                    <template #icon>
                        <Star class="h-5 w-5" />
                    </template>
                </MetricCard>

                <MetricCard
                    label="Comments"
                    :value="blogPost.comments_enabled === false ? 'Closed' : 'Open'"
                >
                    <template #icon>
                        <MessageSquare class="h-5 w-5" />
                    </template>
                </MetricCard>

                <MetricCard
                    label="Published"
                    :value="formatDate(blogPost.published_at)"
                    emphasized
                />
            </div>

            <ShowSection title="Administrative Details">
                <ShowDetailsGrid :columns="3">
                    <DetailRow label="Author" :value="blogPost.author?.name" />
                    <DetailRow label="Created" :value="formatDate(blogPost.created_at)" />
                    <DetailRow label="Updated" :value="formatDate(blogPost.updated_at)" />
                    <DetailRow label="Release" :value="formatDate(blogPost.published_at)" />
                    <DetailRow label="End Date" :value="formatDate(blogPost.expires_at)" />
                    <DetailRow label="Slug" :value="blogPost.slug" break-all />
                </ShowDetailsGrid>
            </ShowSection>

            <ShowSection
                title="Article Preview"
                description="Administrative preview of the article content."
            >
                <article class="mx-auto max-w-6xl overflow-hidden rounded-xl border bg-background">
                    <div
                        v-if="blogPost.header_image_url"
                        class="aspect-[4/1] w-full overflow-hidden bg-muted"
                    >
                        <img
                            :src="blogPost.header_image_url"
                            :alt="blogPost.title"
                            class="h-full w-full object-cover"
                        />
                    </div>

                    <div class="space-y-6 p-8">
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="category in blogPost.categories"
                                :key="category.id"
                                class="rounded-full bg-muted px-3 py-1 text-xs font-medium"
                            >
                                {{ category.name }}
                            </span>
                        </div>

                        <div class="flex items-center gap-4">
                            <img
                                v-if="blogPost.icon_image_url"
                                :src="blogPost.icon_image_url"
                                :alt="blogPost.title"
                                class="h-14 w-14 rounded-full object-cover"
                            />

                            <div>
                                <h2 class="text-4xl font-bold tracking-tight">
                                    {{ blogPost.title }}
                                </h2>

                                <div class="mt-2 text-sm text-muted-foreground">
                                    By {{ blogPost.author?.name ?? 'Unknown Author' }}
                                    <span v-if="blogPost.published_at">
                                        · {{ formatDate(blogPost.published_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p
                            v-if="blogPost.excerpt"
                            class="text-xl leading-8 text-muted-foreground"
                        >
                            {{ blogPost.excerpt }}
                        </p>

                        <img
                            v-if="blogPost.featured_image_url && !blogPost.header_image_url"
                            :src="blogPost.featured_image_url"
                            :alt="blogPost.title"
                            class="max-h-[420px] w-full rounded-lg object-cover"
                        />

                        <div
                            class="blog-content prose prose-neutral max-w-none dark:prose-invert"
                            v-html="blogPost.content"
                        />

                        <div
                            v-if="blogPost.tags.length"
                            class="flex flex-wrap gap-2 border-t pt-6"
                        >
                            <span
                                v-for="tag in blogPost.tags"
                                :key="tag.id"
                                class="rounded-full border px-3 py-1 text-xs"
                            >
                                #{{ tag.name }}
                            </span>
                        </div>
                    </div>
                </article>
            </ShowSection>

            <ShowSection title="SEO">
                <ShowDetailsGrid :columns="1">
                    <DetailRow label="SEO Title" :value="blogPost.seo_title" />
                    <DetailRow
                        label="SEO Description"
                        :value="blogPost.seo_description"
                    />
                </ShowDetailsGrid>
            </ShowSection>

            <ShowSection title="Activity Log">
                <div v-if="activityItems.length" class="space-y-3">
                    <div
                        v-for="item in activityItems"
                        :key="item.id"
                        class="rounded-md border p-3"
                    >
                        <div class="font-medium">
                            {{ item.description }}
                        </div>

                        <div class="text-xs text-muted-foreground">
                            {{ item.user?.name ?? 'System' }}
                            •
                            {{ formatDate(item.created_at) }}
                        </div>
                    </div>
                </div>

                <p v-else class="text-sm text-muted-foreground">
                    No activity recorded.
                </p>
            </ShowSection>

            <ConfirmActionDialog
                v-model:open="deleteDialogOpen"
                title="Delete blog post?"
                :description="`Delete '${blogPost.title}'? This action cannot be undone.`"
                confirm-label="Delete Blog Post"
                destructive
                :loading="deleting"
                @confirm="confirmDelete"
                @cancel="deleteDialogOpen = false"
            />
        </div>
    </AppLayout>
</template>

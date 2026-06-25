<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface Person {
    id: number;
    name: string;
    email?: string;
}

interface Category {
    id: number;
    name: string;
}

interface Tag {
    id: number;
    name: string;
}

interface Activity {
    id: number;
    action?: string;
    description: string;
    created_at: string;

    user?: {
        id: number;
        name: string;
    } | null;
}

interface BlogPost {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string | null;

    featured_image_url: string | null;
    header_image_url: string | null;
    icon_image_url: string | null;

    status: string;
    published_at: string | null;
    created_at: string;
    updated_at: string;

    seo_title: string | null;
    seo_description: string | null;

    is_featured: boolean;
    is_active: boolean;
    views_count: number;

    author: Person | null;
    categories: Category[];
    tags: Tag[];
}

const props = defineProps<{
    blogPost: BlogPost;
    activity?: Activity[];
}>();

const activityItems = computed(() => props.activity ?? []);

function formatDate(value: string | null): string {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString();
}

function deletePost() {
    if (!confirm('Are you sure you want to delete this blog post?')) {
        return;
    }

    router.delete(`/admin/blog-posts/${props.blogPost.slug}`);
}
</script>

<template>
    <Head :title="blogPost.title" />

    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Blog Post Preview
                    </h1>

                    <p class="text-muted-foreground">
                        Preview how this article will appear to users.
                    </p>
                </div>

                <div class="flex gap-2">
                    <Button variant="outline" as-child>
                        <Link href="/admin/blog-posts">
                            Back
                        </Link>
                    </Button>

                    <Button as-child>
                        <Link :href="`/admin/blog-posts/${blogPost.slug}/edit`">
                            Edit
                        </Link>
                    </Button>

                    <Button variant="destructive" @click="deletePost">
                        Delete
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Admin Details</CardTitle>
                </CardHeader>

                <CardContent>
                    <div class="grid gap-4 text-sm md:grid-cols-4">
                        <div>
                            <div class="font-medium">Status</div>
                            <div class="text-muted-foreground">
                                {{ blogPost.status }}
                            </div>
                        </div>

                        <div>
                            <div class="font-medium">Active</div>
                            <div class="text-muted-foreground">
                                {{ blogPost.is_active ? 'Yes' : 'No' }}
                            </div>
                        </div>

                        <div>
                            <div class="font-medium">Featured</div>
                            <div class="text-muted-foreground">
                                {{ blogPost.is_featured ? 'Yes' : 'No' }}
                            </div>
                        </div>

                        <div>
                            <div class="font-medium">Views</div>
                            <div class="text-muted-foreground">
                                {{ blogPost.views_count }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <article class="mx-auto max-w-6xl overflow-hidden rounded-xl border bg-background">
                <div
                    v-if="blogPost.header_image_url"
                    class="aspect-[4/1] w-full overflow-hidden rounded-t-xl bg-muted"
                >
                    <img
                        :src="blogPost.header_image_url"
                        :alt="blogPost.title"
                        class="h-full w-full object-cover object-center"
                    />
                </div>

                <div class="space-y-6 p-8">
                    <div class="space-y-4">
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
                                    <span>
                                        By {{ blogPost.author?.name ?? 'Unknown Author' }}
                                    </span>

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
                    </div>

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

            <Card>
                <CardHeader>
                    <CardTitle>SEO</CardTitle>
                </CardHeader>

                <CardContent class="space-y-4 text-sm">
                    <div>
                        <div class="font-medium">SEO Title</div>
                        <div class="text-muted-foreground">
                            {{ blogPost.seo_title || '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="font-medium">SEO Description</div>
                        <div class="text-muted-foreground">
                            {{ blogPost.seo_description || '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="font-medium">Slug</div>
                        <div class="text-muted-foreground">
                            {{ blogPost.slug }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Activity Log</CardTitle>
                </CardHeader>

                <CardContent>
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

                    <div v-else class="text-sm text-muted-foreground">
                        No activity recorded.
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
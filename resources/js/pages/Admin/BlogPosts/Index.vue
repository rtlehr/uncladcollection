<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';

interface BlogPost {
    id: number;
    title: string;
    slug: string;
    status: string;
    is_featured: boolean;
    is_active: boolean;
    published_at: string | null;
    expires_at: string | null;
    views_count: number;

    author?: {
        id: number;
        name: string;
        email: string;
    };

    categories?: Array<{
        id: number;
        name: string;
    }>;

    tags?: Array<{
        id: number;
        name: string;
    }>;
}

const props = defineProps<{
    blogPosts: {
        data: BlogPost[];
        links: any[];
    };

    filters: {
        search?: string;
        status?: string;
    };

    statuses: string[];
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleDateString() : '-';
}

function postDisplayStatus(post: BlogPost): string {
    const now = new Date();

    if (!post.is_active) {
        return 'Inactive';
    }

    if (post.status === 'draft') {
        return 'Draft';
    }

    if (post.published_at && new Date(post.published_at) > now) {
        return 'Scheduled';
    }

    if (post.expires_at && new Date(post.expires_at) <= now) {
        return 'Expired';
    }

    if (post.status === 'published') {
        return 'Live';
    }

    return post.status;
}

function postStatusClass(post: BlogPost): string {
    const status = postDisplayStatus(post);

    return {
        Live: 'bg-green-100 text-green-700',
        Scheduled: 'bg-blue-100 text-blue-700',
        Draft: 'bg-yellow-100 text-yellow-700',
        Expired: 'bg-gray-100 text-gray-700',
        Inactive: 'bg-red-100 text-red-700',
    }[status] ?? 'bg-muted text-muted-foreground';
}

watch([search, status], () => {
    router.get(
        '/admin/blog-posts',
        {
            search: search.value,
            status: status.value,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
});
</script>

<template>
    <Head title="Blog Posts" />

    <AppLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Blog Posts
                    </h1>

                    <p class="text-muted-foreground">
                        Manage blog articles and content.
                    </p>
                </div>

                <Button as-child>
                    <Link href="/admin/blog-posts/create">
                        New Blog Post
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                </CardHeader>

                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2">
                        <Input
                            v-model="search"
                            placeholder="Search posts..."
                        />

                        <select
                            v-model="status"
                            class="h-10 rounded-md border bg-background px-3"
                        >
                            <option value="">
                                All Statuses
                            </option>

                            <option
                                v-for="item in statuses"
                                :key="item"
                                :value="item"
                            >
                                {{ item }}
                            </option>
                        </select>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>
                        Blog Posts
                    </CardTitle>
                </CardHeader>

                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-3 text-left">
                                        Title
                                    </th>

                                    <th class="py-3 text-left">
                                        Author
                                    </th>

                                    <th class="py-3 text-left">
                                        Status
                                    </th>

                                    <th class="py-3 text-left">
                                        Release
                                    </th>

                                    <th class="py-3 text-left">
                                        End Date
                                    </th>

                                    <th class="py-3 text-left">
                                        Views
                                    </th>

                                    <th class="py-3 text-left">
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="post in blogPosts.data"
                                    :key="post.id"
                                    class="border-b"
                                >
                                    <td class="py-3">
                                        <div class="font-medium">
                                            {{ post.title }}
                                        </div>

                                        <div class="text-xs text-muted-foreground">
                                            {{ post.slug }}
                                        </div>

                                        <div
                                            v-if="post.is_featured"
                                            class="mt-1 text-xs font-medium text-yellow-600"
                                        >
                                            Featured
                                        </div>
                                    </td>

                                    <td class="py-3">
                                        {{ post.author?.name ?? 'Unknown' }}
                                    </td>

                                    <td class="py-3">
                                        <span
                                            class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="postStatusClass(post)"
                                        >
                                            {{ postDisplayStatus(post) }}
                                        </span>
                                    </td>

                                    <td class="py-3">
                                        {{ formatDate(post.published_at) }}
                                    </td>

                                    <td class="py-3">
                                        <span v-if="post.expires_at">
                                            {{ formatDate(post.expires_at) }}
                                        </span>

                                        <span v-else class="italic text-muted-foreground">
                                            Never
                                        </span>
                                    </td>

                                    <td class="py-3">
                                        {{ post.views_count }}
                                    </td>

                                    <td class="py-3">
                                        <div class="flex gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                as-child
                                            >
                                                <Link :href="`/admin/blog-posts/${post.slug}`">
                                                    View
                                                </Link>
                                            </Button>

                                            <Button
                                                size="sm"
                                                as-child
                                            >
                                                <Link :href="`/admin/blog-posts/${post.slug}/edit`">
                                                    Edit
                                                </Link>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="blogPosts.data.length === 0">
                                    <td
                                        colspan="7"
                                        class="py-8 text-center text-muted-foreground"
                                    >
                                        No blog posts found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="blogPosts.links?.length"
                        class="mt-6 flex flex-wrap gap-2"
                    >
                        <template
                            v-for="link in blogPosts.links"
                            :key="link.label"
                        >
                            <Button
                                v-if="link.url"
                                size="sm"
                                variant="outline"
                                as-child
                            >
                                <Link
                                    :href="link.url"
                                    v-html="link.label"
                                />
                            </Button>
                        </template>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
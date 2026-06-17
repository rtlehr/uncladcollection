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
                                        Published
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

                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ post.slug }}
                                        </div>

                                        <div
                                            v-if="post.is_featured"
                                            class="mt-1 text-xs text-yellow-600"
                                        >
                                            Featured
                                        </div>
                                    </td>

                                    <td class="py-3">
                                        {{ post.author?.name ?? 'Unknown' }}
                                    </td>

                                    <td class="py-3">
                                        {{ post.status }}
                                    </td>

                                    <td class="py-3">
                                        {{
                                            post.published_at
                                                ? new Date(post.published_at).toLocaleDateString()
                                                : '-'
                                        }}
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
                                        colspan="6"
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
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { MessageSquare, Search } from '@lucide/vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type CommentUser = {
    id: number;
    name: string;
    username?: string | null;
    email?: string | null;
    avatar_url?: string | null;
};

type Commentable = {
    id: number;
    title?: string;
    slug?: string;
};

type Comment = {
    id: number;
    body: string;
    status: string;
    depth: number;
    likes_count: number;
    reports_count: number;
    is_pinned: boolean;
    created_at: string;
    user: CommentUser | null;
    commentable: Commentable | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    comments: {
        data: Comment[];
        links: PaginationLink[];
    };
    filters: {
        search?: string;
        status?: string;
        filter?: string;
    };
    statuses: string[];
}>();

function updateFilters(extra: Record<string, string>) {
    router.get('/admin/comments', {
        search: props.filters.search ?? '',
        status: props.filters.status ?? '',
        filter: props.filters.filter ?? '',
        ...extra,
    }, {
        preserveState: true,
        replace: true,
    });
}

function action(url: string, method: 'patch' | 'delete' = 'patch') {
    router[method](url, {}, {
        preserveScroll: true,
    });
}

function formatDate(date: string) {
    return new Date(date).toLocaleString();
}
</script>

<template>
    <Head title="Comment Moderation" />

    <AppLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Comment Moderation
                    </h1>

                    <p class="text-muted-foreground">
                        Review, approve, hide, pin, and manage blog comments.
                    </p>
                </div>

                <Button variant="outline" as-child>
                    <Link href="/admin/comments/reports">
                        View Reports
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                </CardHeader>

                <CardContent class="grid gap-4 md:grid-cols-3">
                    <div class="relative">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />

                        <Input
                            :model-value="filters.search"
                            class="pl-9"
                            placeholder="Search comments or users..."
                            @update:model-value="updateFilters({ search: String($event) })"
                        />
                    </div>

                    <select
                        :value="filters.status"
                        class="h-10 rounded-md border bg-background px-3 text-sm"
                        @change="updateFilters({ status: ($event.target as HTMLSelectElement).value })"
                    >
                        <option value="">All Statuses</option>

                        <option
                            v-for="status in statuses"
                            :key="status"
                            :value="status"
                        >
                            {{ status }}
                        </option>
                    </select>

                    <select
                        :value="filters.filter"
                        class="h-10 rounded-md border bg-background px-3 text-sm"
                        @change="updateFilters({ filter: ($event.target as HTMLSelectElement).value })"
                    >
                        <option value="">All Comments</option>
                        <option value="pending">Pending</option>
                        <option value="reported">Reported</option>
                        <option value="pinned">Pinned</option>
                    </select>
                </CardContent>
            </Card>

            <div class="space-y-4">
                <Card
                    v-for="comment in comments.data"
                    :key="comment.id"
                >
                    <CardContent class="p-5">
                        <div class="flex gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-muted">
                                <MessageSquare class="h-5 w-5 text-muted-foreground" />
                            </div>

                            <div class="min-w-0 flex-1 space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium">
                                        {{ comment.user?.username || comment.user?.name || 'Unknown User' }}
                                    </span>

                                    <span class="text-xs text-muted-foreground">
                                        {{ formatDate(comment.created_at) }}
                                    </span>

                                    <span class="rounded-full bg-muted px-2 py-0.5 text-xs">
                                        {{ comment.status }}
                                    </span>

                                    <span
                                        v-if="comment.is_pinned"
                                        class="rounded-full bg-muted px-2 py-0.5 text-xs"
                                    >
                                        pinned
                                    </span>

                                    <span
                                        v-if="comment.reports_count > 0"
                                        class="rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700"
                                    >
                                        {{ comment.reports_count }} reports
                                    </span>
                                </div>

                                <div class="rounded-md border bg-muted/30 p-3 text-sm leading-6 whitespace-pre-line">
                                    {{ comment.body }}
                                </div>

                                <div
                                    v-if="comment.commentable"
                                    class="text-sm text-muted-foreground"
                                >
                                    Article:
                                    <Link
                                        v-if="comment.commentable.slug"
                                        :href="`/blog/${comment.commentable.slug}`"
                                        class="font-medium underline"
                                    >
                                        {{ comment.commentable.title || 'View Article' }}
                                    </Link>

                                    <span v-else>
                                        {{ comment.commentable.title || 'Unknown Article' }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        v-if="comment.status !== 'approved'"
                                        size="sm"
                                        @click="action(`/admin/comments/${comment.id}/approve`)"
                                    >
                                        Approve
                                    </Button>

                                    <Button
                                        v-if="comment.status !== 'hidden'"
                                        size="sm"
                                        variant="outline"
                                        @click="action(`/admin/comments/${comment.id}/hide`)"
                                    >
                                        Hide
                                    </Button>

                                    <Button
                                        v-if="comment.status === 'hidden' || comment.status === 'spam'"
                                        size="sm"
                                        variant="outline"
                                        @click="action(`/admin/comments/${comment.id}/restore`)"
                                    >
                                        Restore
                                    </Button>

                                    <Button
                                        v-if="!comment.is_pinned"
                                        size="sm"
                                        variant="outline"
                                        @click="action(`/admin/comments/${comment.id}/pin`)"
                                    >
                                        Pin
                                    </Button>

                                    <Button
                                        v-else
                                        size="sm"
                                        variant="outline"
                                        @click="action(`/admin/comments/${comment.id}/unpin`)"
                                    >
                                        Unpin
                                    </Button>

                                    <Button
                                        v-if="comment.status !== 'spam'"
                                        size="sm"
                                        variant="outline"
                                        @click="action(`/admin/comments/${comment.id}/spam`)"
                                    >
                                        Spam
                                    </Button>

                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        @click="action(`/admin/comments/${comment.id}`, 'delete')"
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="comments.data.length === 0">
                    <CardContent class="p-8 text-center text-muted-foreground">
                        No comments found.
                    </CardContent>
                </Card>
            </div>

            <div
                v-if="comments.links.length > 3"
                class="flex flex-wrap justify-center gap-2"
            >
                <Link
                    v-for="link in comments.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="rounded-md border px-3 py-2 text-sm"
                    :class="{
                        'bg-primary text-primary-foreground': link.active,
                        'pointer-events-none opacity-50': !link.url,
                    }"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>
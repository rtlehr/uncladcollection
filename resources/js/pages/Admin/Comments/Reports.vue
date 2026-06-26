<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Flag } from '@lucide/vue';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type User = {
    id: number;
    name: string;
    username?: string | null;
    email?: string | null;
};

type Comment = {
    id: number;
    body: string;
    status: string;
    user?: User | null;
};

type Report = {
    id: number;
    reason: string | null;
    details: string | null;
    status: string;
    created_at: string;
    user?: User | null;
    reviewer?: User | null;
    comment?: Comment | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    reports: {
        data: Report[];
        links: PaginationLink[];
    };
    filters: {
        status?: string;
    };
    statuses: string[];
}>();

function updateStatus(status: string) {
    router.get('/admin/comments/reports', {
        status,
    }, {
        preserveState: true,
        replace: true,
    });
}

function patch(url: string) {
    router.patch(url, {}, {
        preserveScroll: true,
    });
}

function formatDate(date: string) {
    return new Date(date).toLocaleString();
}
</script>

<template>
    <Head title="Comment Reports" />

    <AppLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Comment Reports
                    </h1>

                    <p class="text-muted-foreground">
                        Review reports submitted by members.
                    </p>
                </div>

                <Button variant="outline" as-child>
                    <Link href="/admin/comments">
                        Back to Comments
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                </CardHeader>

                <CardContent>
                    <select
                        :value="filters.status"
                        class="h-10 w-full rounded-md border bg-background px-3 text-sm md:w-64"
                        @change="updateStatus(($event.target as HTMLSelectElement).value)"
                    >
                        <option value="">All Reports</option>

                        <option
                            v-for="status in statuses"
                            :key="status"
                            :value="status"
                        >
                            {{ status }}
                        </option>
                    </select>
                </CardContent>
            </Card>

            <div class="space-y-4">
                <Card
                    v-for="report in reports.data"
                    :key="report.id"
                >
                    <CardContent class="p-5">
                        <div class="flex gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-muted">
                                <Flag class="h-5 w-5 text-muted-foreground" />
                            </div>

                            <div class="min-w-0 flex-1 space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium">
                                        Reported by {{ report.user?.username || report.user?.name || 'Unknown User' }}
                                    </span>

                                    <span class="text-xs text-muted-foreground">
                                        {{ formatDate(report.created_at) }}
                                    </span>

                                    <span class="rounded-full bg-muted px-2 py-0.5 text-xs">
                                        {{ report.status }}
                                    </span>

                                    <span class="rounded-full bg-muted px-2 py-0.5 text-xs">
                                        {{ report.reason || 'other' }}
                                    </span>
                                </div>

                                <div
                                    v-if="report.details"
                                    class="rounded-md border bg-muted/30 p-3 text-sm"
                                >
                                    {{ report.details }}
                                </div>

                                <div
                                    v-if="report.comment"
                                    class="rounded-md border p-3"
                                >
                                    <div class="mb-2 text-xs text-muted-foreground">
                                        Comment by {{ report.comment.user?.username || report.comment.user?.name || 'Unknown User' }}
                                    </div>

                                    <div class="whitespace-pre-line text-sm leading-6">
                                        {{ report.comment.body }}
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        size="sm"
                                        @click="patch(`/admin/comment-reports/${report.id}/reviewed`)"
                                    >
                                        Mark Reviewed
                                    </Button>

                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="patch(`/admin/comment-reports/${report.id}/dismiss`)"
                                    >
                                        Dismiss
                                    </Button>

                                    <Button
                                        v-if="report.comment"
                                        size="sm"
                                        variant="outline"
                                        @click="patch(`/admin/comments/${report.comment.id}/hide`)"
                                    >
                                        Hide Comment
                                    </Button>

                                    <Button
                                        v-if="report.comment"
                                        size="sm"
                                        variant="destructive"
                                        @click="router.delete(`/admin/comments/${report.comment.id}`, { preserveScroll: true })"
                                    >
                                        Delete Comment
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="reports.data.length === 0">
                    <CardContent class="p-8 text-center text-muted-foreground">
                        No reports found.
                    </CardContent>
                </Card>
            </div>

            <div
                v-if="reports.links.length > 3"
                class="flex flex-wrap justify-center gap-2"
            >
                <Link
                    v-for="link in reports.links"
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
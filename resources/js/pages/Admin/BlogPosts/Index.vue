<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ActionToolbar from '@/Components/Admin/ActionToolbar.vue';
import FilterToolbar from '@/Components/Admin/FilterToolbar.vue';
import SearchToolbar from '@/Components/Admin/SearchToolbar.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import DataTable from '@/Components/Tables/DataTable.vue';
import DataTableEmpty from '@/Components/Tables/DataTableEmpty.vue';
import DataTableHeaderCell from '@/Components/Tables/DataTableHeaderCell.vue';
import { Button } from '@/components/ui/button';

import type {
    AdminBlogFilters,
    PaginatedAdminBlogPosts,
} from '@/types/adminBlog';

const props = defineProps<{
    blogPosts: PaginatedAdminBlogPosts;
    filters: AdminBlogFilters;
    statuses: string[];
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleDateString() : '—';
}

function displayStatus(post: PaginatedAdminBlogPosts['data'][number]): string {
    const now = new Date();

    if (!post.is_active) {
return 'inactive';
}

    if (post.status === 'draft') {
return 'draft';
}

    if (post.published_at && new Date(post.published_at) > now) {
return 'scheduled';
}

    if (post.expires_at && new Date(post.expires_at) <= now) {
return 'expired';
}

    if (post.status === 'published') {
return 'live';
}

    return post.status;
}

function reload() {
    router.get('/admin/blog-posts', {
        search: search.value || undefined,
        status: status.value || undefined,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    status.value = '';

    router.get('/admin/blog-posts', {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function sortBy(column: string) {
    const direction =
        props.filters.sort === column && props.filters.direction === 'asc'
            ? 'desc'
            : 'asc';

    router.get('/admin/blog-posts', {
        search: search.value || undefined,
        status: status.value || undefined,
        sort: column,
        direction,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Blog Posts" />

    <AppLayout>
        <div class="space-y-6 p-6">
            <PageHeader
                title="Blog Posts"
                description="Manage blog articles and content."
            />

            <ActionToolbar align="end">
                <template #secondary>
                    <Button as-child>
                        <Link href="/admin/blog-posts/create">
                            New Blog Post
                        </Link>
                    </Button>
                </template>
            </ActionToolbar>

            <FilterToolbar :columns="2" compact>
                <SearchToolbar
                    v-model="search"
                    placeholder="Search posts..."
                    :show-reset="false"
                    @search="reload"
                />

                <select
                    v-model="status"
                    class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                    @change="reload"
                >
                    <option value="">All Statuses</option>

                    <option
                        v-for="item in statuses"
                        :key="item"
                        :value="item"
                    >
                        {{ item }}
                    </option>
                </select>

                <template #actions>
                    <Button type="button" @click="reload">
                        Apply Filters
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        @click="resetFilters"
                    >
                        Reset
                    </Button>
                </template>
            </FilterToolbar>

            <DataTable min-width="1050px">
                <thead>
                    <tr class="border-b bg-muted/30">
                        <DataTableHeaderCell
                            label="Title"
                            column="title"
                            sortable
                            :current-sort="filters.sort"
                            :current-direction="filters.direction"
                            @sort="sortBy"
                        />

                        <DataTableHeaderCell label="Author" />

                        <DataTableHeaderCell
                            label="Status"
                            column="status"
                            sortable
                            :current-sort="filters.sort"
                            :current-direction="filters.direction"
                            @sort="sortBy"
                        />

                        <DataTableHeaderCell
                            label="Release"
                            column="published_at"
                            sortable
                            :current-sort="filters.sort"
                            :current-direction="filters.direction"
                            @sort="sortBy"
                        />

                        <DataTableHeaderCell
                            label="End Date"
                            column="expires_at"
                            sortable
                            :current-sort="filters.sort"
                            :current-direction="filters.direction"
                            @sort="sortBy"
                        />

                        <DataTableHeaderCell
                            label="Views"
                            column="views_count"
                            sortable
                            :current-sort="filters.sort"
                            :current-direction="filters.direction"
                            @sort="sortBy"
                        />

                        <DataTableHeaderCell label="Actions" align="right" />
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="post in blogPosts.data"
                        :key="post.id"
                        class="border-b last:border-0 hover:bg-muted/20"
                    >
                        <td class="p-4">
                            <div class="font-medium">{{ post.title }}</div>
                            <div class="font-mono text-xs text-muted-foreground">
                                {{ post.slug }}
                            </div>

                            <StatusBadge
                                v-if="post.is_featured"
                                class="mt-2"
                                status="featured"
                            />
                        </td>

                        <td class="p-4">
                            {{ post.author?.name ?? 'Unknown' }}
                        </td>

                        <td class="p-4">
                            <StatusBadge :status="displayStatus(post)" />
                        </td>

                        <td class="p-4">
                            {{ formatDate(post.published_at) }}
                        </td>

                        <td class="p-4">
                            {{ post.expires_at ? formatDate(post.expires_at) : 'Never' }}
                        </td>

                        <td class="p-4">
                            {{ post.views_count.toLocaleString() }}
                        </td>

                        <td class="p-4">
                            <div class="flex justify-end gap-2">
                                <Button size="sm" variant="outline" as-child>
                                    <Link :href="`/admin/blog-posts/${post.slug}`">
                                        View
                                    </Link>
                                </Button>

                                <Button size="sm" variant="outline" as-child>
                                    <Link :href="`/admin/blog-posts/${post.slug}/edit`">
                                        Edit
                                    </Link>
                                </Button>
                            </div>
                        </td>
                    </tr>

                    <DataTableEmpty
                        v-if="blogPosts.data.length === 0"
                        :colspan="7"
                        message="No blog posts found."
                    />
                </tbody>
            </DataTable>

            <Pagination
                :links="blogPosts.links"
                :from="blogPosts.from ?? null"
                :to="blogPosts.to ?? null"
                :total="blogPosts.total ?? null"
                item-label="blog posts"
                :show-summary="blogPosts.total !== undefined"
            />
        </div>
    </AppLayout>
</template>

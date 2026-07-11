<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ActionToolbar from '@/Components/Admin/ActionToolbar.vue';
import CommentModerationCard from '@/Components/Admin/CommentModerationCard.vue';
import FilterToolbar from '@/Components/Admin/FilterToolbar.vue';
import SearchToolbar from '@/Components/Admin/SearchToolbar.vue';
import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import EmptyState from '@/Components/Shared/EmptyState.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { Button } from '@/components/ui/button';

import type {
    AdminCommentFilters,
    AdminCommentRecord,
    PaginatedAdminComments,
} from '@/types/adminComment';

const props = defineProps<{
    comments: PaginatedAdminComments;
    filters: AdminCommentFilters;
    statuses: string[];
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const specialFilter = ref(props.filters.filter ?? '');

const selectedComment = ref<AdminCommentRecord | null>(null);
const deleteDialogOpen = ref(false);
const deleting = ref(false);

function reload() {
    router.get('/admin/comments', {
        search: search.value || undefined,
        status: status.value || undefined,
        filter: specialFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    status.value = '';
    specialFilter.value = '';

    router.get('/admin/comments', {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function patch(url: string) {
    router.patch(url, {}, {
        preserveScroll: true,
    });
}

function requestDelete(comment: AdminCommentRecord) {
    selectedComment.value = comment;
    deleteDialogOpen.value = true;
}

function confirmDelete() {
    if (!selectedComment.value) return;

    deleting.value = true;

    router.delete(`/admin/comments/${selectedComment.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteDialogOpen.value = false;
            selectedComment.value = null;
        },
    });
}
</script>

<template>
    <Head title="Comment Moderation" />

    <AppLayout>
        <div class="space-y-6 p-6">
            <PageHeader
                title="Comment Moderation"
                description="Review, approve, hide, pin, and manage blog comments."
            />

            <ActionToolbar align="end">
                <template #secondary>
                    <Button variant="outline" as-child>
                        <Link href="/admin/comments/reports">
                            View Reports
                        </Link>
                    </Button>
                </template>
            </ActionToolbar>

            <FilterToolbar :columns="3" compact>
                <SearchToolbar
                    v-model="search"
                    placeholder="Search comments or users..."
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

                <select
                    v-model="specialFilter"
                    class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                    @change="reload"
                >
                    <option value="">All Comments</option>
                    <option value="pending">Pending</option>
                    <option value="reported">Reported</option>
                    <option value="pinned">Pinned</option>
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

            <div
                v-if="comments.data.length"
                class="space-y-4"
            >
                <CommentModerationCard
                    v-for="comment in comments.data"
                    :key="comment.id"
                    :comment="comment"
                    @approve="patch(`/admin/comments/${$event.id}/approve`)"
                    @hide="patch(`/admin/comments/${$event.id}/hide`)"
                    @restore="patch(`/admin/comments/${$event.id}/restore`)"
                    @pin="patch(`/admin/comments/${$event.id}/pin`)"
                    @unpin="patch(`/admin/comments/${$event.id}/unpin`)"
                    @spam="patch(`/admin/comments/${$event.id}/spam`)"
                    @delete="requestDelete"
                />
            </div>

            <EmptyState
                v-else
                title="No comments found"
                description="Try adjusting the moderation filters."
            />

            <Pagination
                :links="comments.links"
                :from="comments.from ?? null"
                :to="comments.to ?? null"
                :total="comments.total ?? null"
                item-label="comments"
                :show-summary="comments.total !== undefined"
            />

            <ConfirmActionDialog
                v-model:open="deleteDialogOpen"
                title="Delete comment?"
                :description="
                    selectedComment
                        ? `Delete comment #${selectedComment.id}? This action cannot be undone.`
                        : 'This action cannot be undone.'
                "
                confirm-label="Delete Comment"
                destructive
                :loading="deleting"
                @confirm="confirmDelete"
                @cancel="selectedComment = null"
            />
        </div>
    </AppLayout>
</template>

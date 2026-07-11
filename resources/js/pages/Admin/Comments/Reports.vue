<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ActionToolbar from '@/Components/Admin/ActionToolbar.vue';
import CommentReportCard from '@/Components/Admin/CommentReportCard.vue';
import FilterToolbar from '@/Components/Admin/FilterToolbar.vue';
import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import EmptyState from '@/Components/Shared/EmptyState.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import Pagination from '@/Components/Shared/Pagination.vue';
import { Button } from '@/components/ui/button';

import type {
    AdminCommentReport,
    AdminCommentReportFilters,
    PaginatedAdminCommentReports,
} from '@/types/adminComment';

const props = defineProps<{
    reports: PaginatedAdminCommentReports;
    filters: AdminCommentReportFilters;
    statuses: string[];
}>();

const status = ref(props.filters.status ?? '');

const selectedReport = ref<AdminCommentReport | null>(null);
const deleteDialogOpen = ref(false);
const deleting = ref(false);

function reload() {
    router.get('/admin/comments/reports', {
        status: status.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    status.value = '';

    router.get('/admin/comments/reports', {}, {
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

function requestDelete(report: AdminCommentReport) {
    selectedReport.value = report;
    deleteDialogOpen.value = true;
}

function confirmDelete() {
    if (!selectedReport.value?.comment) return;

    deleting.value = true;

    router.delete(`/admin/comments/${selectedReport.value.comment.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteDialogOpen.value = false;
            selectedReport.value = null;
        },
    });
}
</script>

<template>
    <Head title="Comment Reports" />

    <AppLayout>
        <div class="space-y-6 p-6">
            <PageHeader
                title="Comment Reports"
                description="Review reports submitted by members."
            />

            <ActionToolbar align="end">
                <template #secondary>
                    <Button variant="outline" as-child>
                        <Link href="/admin/comments">
                            Back to Comments
                        </Link>
                    </Button>
                </template>
            </ActionToolbar>

            <FilterToolbar :columns="1" compact>
                <select
                    v-model="status"
                    class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                    @change="reload"
                >
                    <option value="">All Reports</option>

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

            <div
                v-if="reports.data.length"
                class="space-y-4"
            >
                <CommentReportCard
                    v-for="report in reports.data"
                    :key="report.id"
                    :report="report"
                    @reviewed="patch(`/admin/comment-reports/${$event.id}/reviewed`)"
                    @dismiss="patch(`/admin/comment-reports/${$event.id}/dismiss`)"
                    @hide-comment="
                        $event.comment
                            ? patch(`/admin/comments/${$event.comment.id}/hide`)
                            : undefined
                    "
                    @delete-comment="requestDelete"
                />
            </div>

            <EmptyState
                v-else
                title="No reports found"
                description="There are no comment reports matching the current filter."
            />

            <Pagination
                :links="reports.links"
                :from="reports.from ?? null"
                :to="reports.to ?? null"
                :total="reports.total ?? null"
                item-label="reports"
                :show-summary="reports.total !== undefined"
            />

            <ConfirmActionDialog
                v-model:open="deleteDialogOpen"
                title="Delete reported comment?"
                :description="
                    selectedReport?.comment
                        ? `Delete comment #${selectedReport.comment.id}? This action cannot be undone.`
                        : 'This action cannot be undone.'
                "
                confirm-label="Delete Comment"
                destructive
                :loading="deleting"
                @confirm="confirmDelete"
                @cancel="selectedReport = null"
            />
        </div>
    </AppLayout>
</template>

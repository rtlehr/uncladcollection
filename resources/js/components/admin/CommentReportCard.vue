<script setup lang="ts">
import { Flag } from '@lucide/vue';

import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { Button } from '@/components/ui/button';

import type { AdminCommentReport } from '@/types/adminComment';

defineProps<{
    report: AdminCommentReport;
}>();

const emit = defineEmits<{
    reviewed: [report: AdminCommentReport];
    dismiss: [report: AdminCommentReport];
    hideComment: [report: AdminCommentReport];
    deleteComment: [report: AdminCommentReport];
}>();

function formatDate(date: string): string {
    return new Date(date).toLocaleString();
}
</script>

<template>
    <article class="rounded-lg border bg-card p-5 shadow-sm">
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

                    <StatusBadge :status="report.status" />

                    <StatusBadge
                        status="report_reason"
                        :label="report.reason || 'other'"
                        tone="neutral"
                    />
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
                        Comment by
                        {{ report.comment.user?.username || report.comment.user?.name || 'Unknown User' }}
                    </div>

                    <div class="whitespace-pre-line text-sm leading-6">
                        {{ report.comment.body }}
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button
                        size="sm"
                        @click="emit('reviewed', report)"
                    >
                        Mark Reviewed
                    </Button>

                    <Button
                        size="sm"
                        variant="outline"
                        @click="emit('dismiss', report)"
                    >
                        Dismiss
                    </Button>

                    <Button
                        v-if="report.comment"
                        size="sm"
                        variant="outline"
                        @click="emit('hideComment', report)"
                    >
                        Hide Comment
                    </Button>

                    <Button
                        v-if="report.comment"
                        size="sm"
                        variant="destructive"
                        @click="emit('deleteComment', report)"
                    >
                        Delete Comment
                    </Button>
                </div>
            </div>
        </div>
    </article>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MessageSquare } from '@lucide/vue';

import StatusBadge from '@/Components/Shared/StatusBadge.vue';
import { Button } from '@/components/ui/button';

import type { AdminCommentRecord } from '@/types/adminComment';

defineProps<{
    comment: AdminCommentRecord;
}>();

const emit = defineEmits<{
    approve: [comment: AdminCommentRecord];
    hide: [comment: AdminCommentRecord];
    restore: [comment: AdminCommentRecord];
    pin: [comment: AdminCommentRecord];
    unpin: [comment: AdminCommentRecord];
    spam: [comment: AdminCommentRecord];
    delete: [comment: AdminCommentRecord];
}>();

function formatDate(date: string): string {
    return new Date(date).toLocaleString();
}
</script>

<template>
    <article class="rounded-lg border bg-card p-5 shadow-sm">
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

                    <StatusBadge :status="comment.status" />

                    <StatusBadge
                        v-if="comment.is_pinned"
                        status="pinned"
                    />

                    <StatusBadge
                        v-if="comment.reports_count > 0"
                        status="reported"
                        :label="`${comment.reports_count} reports`"
                        tone="danger"
                    />
                </div>

                <div class="whitespace-pre-line rounded-md border bg-muted/30 p-3 text-sm leading-6">
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
                        class="font-medium text-primary hover:underline"
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
                        @click="emit('approve', comment)"
                    >
                        Approve
                    </Button>

                    <Button
                        v-if="comment.status !== 'hidden'"
                        size="sm"
                        variant="outline"
                        @click="emit('hide', comment)"
                    >
                        Hide
                    </Button>

                    <Button
                        v-if="comment.status === 'hidden' || comment.status === 'spam'"
                        size="sm"
                        variant="outline"
                        @click="emit('restore', comment)"
                    >
                        Restore
                    </Button>

                    <Button
                        v-if="!comment.is_pinned"
                        size="sm"
                        variant="outline"
                        @click="emit('pin', comment)"
                    >
                        Pin
                    </Button>

                    <Button
                        v-else
                        size="sm"
                        variant="outline"
                        @click="emit('unpin', comment)"
                    >
                        Unpin
                    </Button>

                    <Button
                        v-if="comment.status !== 'spam'"
                        size="sm"
                        variant="outline"
                        @click="emit('spam', comment)"
                    >
                        Spam
                    </Button>

                    <Button
                        size="sm"
                        variant="destructive"
                        @click="emit('delete', comment)"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </div>
    </article>
</template>

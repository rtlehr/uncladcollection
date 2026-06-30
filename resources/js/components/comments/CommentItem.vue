<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useAuth } from '@/composables/useAuth';

type CommentUser = {
    id: number;
    name: string;
    username?: string | null;
    avatar_url?: string | null;
};

type Comment = {
    id: number;
    body: string;
    status: string;
    user: CommentUser;
    parent_id?: number | null;
    depth: number;
    likes_count: number;
    reports_count: number;
    is_pinned: boolean;
    is_edited: boolean;
    created_at: string;
    updated_at: string;
    approved_replies?: Comment[];
};

const props = defineProps<{
    comment: Comment;
    blogPostSlug: string;
    authUser: any;
}>();

const { can } = useAuth();

const replying = ref(false);
const editing = ref(false);
const replyBody = ref('');
const editBody = ref(props.comment.body);
const processing = ref(false);
const deleting = ref(false);

const showDeleteDialog = ref(false);
const deleteMode = ref<'own' | 'admin'>('own');

const canManageComments = computed(() => can('manage_comments'));
const canReply = computed(() => props.authUser && props.comment.depth < 2);
const canEdit = computed(() => props.authUser?.id === props.comment.user.id);
const displayName = computed(() => props.comment.user.username || props.comment.user.name);

const moderationAction = ref<'hide' | 'restore' | 'spam' | 'delete' | null>(null);
const moderating = ref(false);

const showModerationDialog = ref(false);

const moderationDialogTitle = computed(() => {
    switch (moderationAction.value) {
        case 'hide':
            return 'Hide comment?';
        case 'restore':
            return 'Restore comment?';
        case 'spam':
            return 'Mark comment as spam?';
        default:
            return 'Moderate comment?';
    }
});

const moderationDialogDescription = computed(() => {
    switch (moderationAction.value) {
        case 'hide':
            return 'This will hide the comment from the public discussion.';
        case 'restore':
            return 'This will restore the comment and make it visible again.';
        case 'spam':
            return 'This will mark the comment as spam and hide it from the discussion.';
        default:
            return 'This moderation action will update the comment.';
    }
});

const moderationConfirmText = computed(() => {
    switch (moderationAction.value) {
        case 'hide':
            return 'Hide Comment';
        case 'restore':
            return 'Restore Comment';
        case 'spam':
            return 'Mark Spam';
        default:
            return 'Confirm';
    }
});

const moderationConfirmVariant = computed(() => {
    return moderationAction.value === 'spam' ? 'destructive' : 'default';
});

function formatDate(date: string) {
    return new Date(date).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function submitReply() {
    if (!replyBody.value.trim()) return;

    processing.value = true;

    router.post(
        `/blog/${props.blogPostSlug}/comments`,
        {
            body: replyBody.value,
            parent_id: props.comment.id,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                replyBody.value = '';
                replying.value = false;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

function updateComment() {
    if (!editBody.value.trim()) return;

    processing.value = true;

    router.put(
        `/comments/${props.comment.id}`,
        { body: editBody.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                editing.value = false;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

function likeComment() {
    router.post(`/comments/${props.comment.id}/like`, {}, {
        preserveScroll: true,
    });
}

function reportComment() {
    router.post(`/comments/${props.comment.id}/report`, {
        reason: 'other',
        details: null,
    }, {
        preserveScroll: true,
    });
}

function confirmDelete(mode: 'own' | 'admin') {
    deleteMode.value = mode;
    showDeleteDialog.value = true;
}

function performDeleteComment() {
    deleting.value = true;

    const url = deleteMode.value === 'admin'
        ? `/admin/comments/${props.comment.id}`
        : `/comments/${props.comment.id}`;

    router.delete(url, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            showDeleteDialog.value = false;
        },
    });
}

function confirmModerationAction(action: 'hide' | 'restore' | 'spam' | 'delete') {
    moderationAction.value = action;

    if (action === 'delete') {
        deleteMode.value = 'admin';
        showDeleteDialog.value = true;
        return;
    }

    showModerationDialog.value = true;
}

function performModerationAction() {
    if (!moderationAction.value) return;

    moderating.value = true;

    router.patch(`/admin/comments/${props.comment.id}/${moderationAction.value}`, {}, {
        preserveScroll: true,
        onFinish: () => {
            moderating.value = false;
            showModerationDialog.value = false;
            moderationAction.value = null;
        },
    });
}

</script>

<template>
    <article
        class="rounded-lg border bg-background p-4"
        :class="comment.depth > 0 ? 'ml-6 mt-4 border-l-4' : ''"
    >
        <div class="flex gap-3">
            <img
                v-if="comment.user.avatar_url"
                :src="comment.user.avatar_url"
                :alt="displayName"
                class="h-10 w-10 rounded-full object-cover"
            >

            <div
                v-else
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold"
            >
                {{ displayName.charAt(0).toUpperCase() }}
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-medium">
                        {{ displayName }}
                    </span>

                    <span class="text-xs text-muted-foreground">
                        {{ formatDate(comment.created_at) }}
                    </span>

                    <span
                        v-if="comment.is_pinned"
                        class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium"
                    >
                        Pinned
                    </span>

                    <span
                        v-if="comment.is_edited"
                        class="text-xs text-muted-foreground"
                    >
                        edited
                    </span>
                </div>

                <div v-if="editing" class="mt-3 space-y-3">
                    <Textarea
                        v-model="editBody"
                        rows="3"
                        class="resize-none"
                    />

                    <div class="flex gap-2">
                        <Button
                            size="sm"
                            :disabled="processing || !editBody.trim()"
                            @click="updateComment"
                        >
                            Save
                        </Button>

                        <Button
                            size="sm"
                            variant="ghost"
                            @click="editing = false"
                        >
                            Cancel
                        </Button>
                    </div>
                </div>

                <p
                    v-else
                    class="mt-3 whitespace-pre-line text-sm leading-6 text-foreground"
                >
                    {{ comment.body }}
                </p>

                <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
                    <Button
                        v-if="authUser"
                        size="sm"
                        variant="ghost"
                        @click="likeComment"
                    >
                        Helpful · {{ comment.likes_count }}
                    </Button>

                    <Button
                        v-if="canReply"
                        size="sm"
                        variant="ghost"
                        @click="replying = !replying"
                    >
                        Reply
                    </Button>

                    <Button
                        v-if="canEdit"
                        size="sm"
                        variant="ghost"
                        @click="editing = true"
                    >
                        Edit
                    </Button>

                    <Button
                        v-if="canEdit"
                        size="sm"
                        variant="ghost"
                        @click="confirmDelete('own')"
                    >
                        Delete
                    </Button>

                    <Button
                        v-if="authUser && !canEdit"
                        size="sm"
                        variant="ghost"
                        @click="reportComment"
                    >
                        Report
                    </Button>
                </div>

                <div
                    v-if="canManageComments"
                    class="mt-3 rounded-md border bg-muted/20 p-3"
                >
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Moderator
                        </span>

                        <span class="rounded-full bg-muted px-2 py-0.5 text-xs">
                            {{ comment.status }}
                        </span>

                        <span
                            v-if="comment.reports_count > 0"
                            class="rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700"
                        >
                            {{ comment.reports_count }} reports
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-if="comment.status !== 'approved'"
                            size="sm"
                            variant="outline"
                            @click="confirmModerationAction('restore')"
                        >
                            Approve / Restore
                        </Button>

                        <Button
                            v-if="comment.status === 'approved'"
                            size="sm"
                            variant="outline"
                            @click="confirmModerationAction('hide')"
                        >
                            Hide
                        </Button>

                        <Button
                            v-if="!comment.is_pinned"
                            size="sm"
                            variant="outline"
                            @click="router.patch(`/admin/comments/${comment.id}/pin`, {}, { preserveScroll: true })"
                        >
                            Pin
                        </Button>

                        <Button
                            v-else
                            size="sm"
                            variant="outline"
                            @click="router.patch(`/admin/comments/${comment.id}/unpin`, {}, { preserveScroll: true })"
                        >
                            Unpin
                        </Button>

                        <Button
                            v-if="comment.status !== 'spam'"
                            size="sm"
                            variant="outline"
                            @click="confirmModerationAction('spam')"
                        >
                            Spam
                        </Button>

                        <Button
                            size="sm"
                            variant="destructive"
                            @click="confirmModerationAction('delete')"
                        >
                            Admin Delete
                        </Button>
                    </div>
                </div>

                <div v-if="replying" class="mt-4 space-y-3">
                    <Textarea
                        v-model="replyBody"
                        rows="3"
                        placeholder="Write a reply..."
                        class="resize-none"
                    />

                    <div class="flex gap-2">
                        <Button
                            size="sm"
                            :disabled="processing || !replyBody.trim()"
                            @click="submitReply"
                        >
                            Post Reply
                        </Button>

                        <Button
                            size="sm"
                            variant="ghost"
                            @click="replying = false"
                        >
                            Cancel
                        </Button>
                    </div>
                </div>

                <div
                    v-if="comment.approved_replies?.length"
                    class="mt-4 space-y-4"
                >
                    <CommentItem
                        v-for="reply in comment.approved_replies"
                        :key="reply.id"
                        :comment="reply"
                        :blog-post-slug="blogPostSlug"
                        :auth-user="authUser"
                    />
                </div>
            </div>
        </div>
    </article>

    <ConfirmDialog
        v-model:open="showDeleteDialog"
        :loading="deleting"
        title="Delete comment?"
        :description="deleteMode === 'admin'
            ? 'This will delete the comment as a moderator action. This action cannot be undone.'
            : 'This will delete your comment. This action cannot be undone.'"
        confirm-text="Delete Comment"
        confirm-variant="destructive"
        @confirm="performDeleteComment"
    />

    <ConfirmDialog
        v-model:open="showModerationDialog"
        :loading="moderating"
        :title="moderationDialogTitle"
        :description="moderationDialogDescription"
        :confirm-text="moderationConfirmText"
        :confirm-variant="moderationConfirmVariant"
        @confirm="performModerationAction"
    />
    
</template>
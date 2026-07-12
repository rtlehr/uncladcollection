<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import ConfirmDialog from '@/components/common/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useAuth } from '@/composables/useAuth';
import CommentStatusBadge from '@/components/comments/CommentStatusBadge.vue';
import RelativeTime from '@/components/common/RelativeTime.vue';

import {
    Check,
    EyeOff,
    Pin,
    ShieldAlert,
    Trash2,
    ChevronDown,
    ChevronRight,
} from '@lucide/vue';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type CommentUser = {
    id: number;
    name: string;
    username?: string | null;
    avatar_url?: string | null;
};

type Comment = {
    id: number;
    body: string;
    body_html?: string;
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
    blogAuthorId: number;
    authUser: any;
}>();

const { can } = useAuth();

const repliesExpanded = ref(true);

const replies = computed(() => props.comment.approved_replies ?? []);

const replyCount = computed(() => replies.value.length);

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

const isAuthor = computed(() =>
    props.comment.user.id === props.blogAuthorId
);

const isModerator = computed(() =>
    can('manage_comments')
);

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
        class="group rounded-lg border bg-background p-3 transition hover:shadow-sm"
        :class="comment.depth > 0 ? 'mt-3 border-muted bg-muted/10' : ''"
    >
        <div class="flex gap-3">
            <img
                v-if="comment.user.avatar_url"
                :src="comment.user.avatar_url"
                :alt="displayName"
                class="h-10 w-10 rounded-full object-cover ring-2 ring-background"
            />

            <div
                v-else
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary ring-2 ring-background"
            >
                {{ displayName.charAt(0).toUpperCase() }}
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-medium">
                        {{ displayName }}
                    </span>

                    <span
                        v-if="isAuthor"
                        class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary"
                    >
                        Author
                    </span>

                    <span class="text-xs text-muted-foreground">
                        <RelativeTime :date="comment.created_at" />
                    </span>

                    <span
                        v-if="comment.is_pinned"
                        class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                    >
                        <Pin class="h-3 w-3" />
                        Pinned
                    </span>

                    <span
                        v-if="comment.is_edited"
                        class="text-xs text-muted-foreground"
                    >
                        · edited
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

                <div
                    v-else
                    class="comment-markdown mt-3 text-sm leading-6 text-foreground"
                    v-html="comment.body_html || comment.body"
                />

                <div class="mt-3 flex flex-wrap items-center gap-1 text-sm opacity-80 transition-opacity group-hover:opacity-100">
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
                    class="mt-3 flex flex-wrap items-center gap-2 rounded-md border bg-muted/10 px-3 py-2"
                >
                    <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Moderator
                    </span>

                    <CommentStatusBadge :status="comment.status" />

                    <span
                        v-if="comment.reports_count > 0"
                        class="rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700"
                    >
                        {{ comment.reports_count }} reports
                    </span>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button size="sm" variant="ghost" class="ml-auto h-8">
                                Actions
                                <ChevronDown class="ml-1 h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end" class="w-52">
                            <DropdownMenuLabel>
                                Comment Actions
                            </DropdownMenuLabel>

                            <DropdownMenuSeparator />

                            <DropdownMenuItem
                                v-if="comment.status !== 'approved'"
                                @click="confirmModerationAction('restore')"
                            >
                                <Check class="mr-2 h-4 w-4" />
                                Approve / Restore
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                v-if="comment.status === 'approved'"
                                @click="confirmModerationAction('hide')"
                            >
                                <EyeOff class="mr-2 h-4 w-4" />
                                Hide
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                v-if="!comment.is_pinned"
                                @click="router.patch(`/admin/comments/${comment.id}/pin`, {}, { preserveScroll: true })"
                            >
                                <Pin class="mr-2 h-4 w-4" />
                                Pin
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                v-else
                                @click="router.patch(`/admin/comments/${comment.id}/unpin`, {}, { preserveScroll: true })"
                            >
                                <Pin class="mr-2 h-4 w-4" />
                                Unpin
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                v-if="comment.status !== 'spam'"
                                @click="confirmModerationAction('spam')"
                            >
                                <ShieldAlert class="mr-2 h-4 w-4" />
                                Mark Spam
                            </DropdownMenuItem>

                            <DropdownMenuSeparator />

                            <DropdownMenuItem
                                class="text-red-600 focus:text-red-600"
                                @click="confirmModerationAction('delete')"
                            >
                                <Trash2 class="mr-2 h-4 w-4" />
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <div v-if="replying" class="mt-4 space-y-3">
                    <Textarea
                        v-model="replyBody"
                        rows="3"
                        placeholder="Write a reply..."
                        class="resize-none"
                    />

                    <div class="mt-2 text-xs text-muted-foreground">
                        Supports Markdown:
                        <code>**bold**</code>,
                        <code>*italic*</code>,
                        <code>- lists</code>,
                        <code>[links](https://...)</code>
                    </div>

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
                    v-if="replyCount"
                    class="mt-3"
                >
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        class="mb-2 h-8 px-0 text-xs text-muted-foreground hover:text-foreground"
                        @click="repliesExpanded = !repliesExpanded"
                    >
                        <ChevronDown
                            v-if="repliesExpanded"
                            class="mr-1 h-4 w-4"
                        />

                        <ChevronRight
                            v-else
                            class="mr-1 h-4 w-4"
                        />

                        <span v-if="repliesExpanded">
                            Hide replies ({{ replyCount }})
                        </span>

                        <span v-else>
                            View replies ({{ replyCount }})
                        </span>
                    </Button>

                    <div
                        v-if="repliesExpanded"
                        class="relative ml-4 border-l pl-4"
                    >
                        <CommentItem
                            v-for="reply in replies"
                            :key="reply.id"
                            :comment="reply"
                            :blog-post-slug="blogPostSlug"
                            :blog-author-id="blogAuthorId"
                            :auth-user="authUser"
                        />
                    </div>
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

 <style scoped>
.comment-markdown :deep(p) {
    margin-bottom: 0.75rem;
}

.comment-markdown :deep(p:last-child) {
    margin-bottom: 0;
}

.comment-markdown :deep(ul),
.comment-markdown :deep(ol) {
    margin: 0.5rem 0 0.5rem 1.25rem;
}

.comment-markdown :deep(ul) {
    list-style: disc;
}

.comment-markdown :deep(ol) {
    list-style: decimal;
}

.comment-markdown :deep(a) {
    text-decoration: underline;
    font-weight: 500;
}

.comment-markdown :deep(strong) {
    font-weight: 700;
}

.comment-markdown :deep(em) {
    font-style: italic;
}
</style>
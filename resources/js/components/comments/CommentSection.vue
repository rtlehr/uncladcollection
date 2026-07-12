<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import {
    MessageCircle,
    Send,
} from '@lucide/vue';
import {
    computed,
    ref,
} from 'vue';

import CommentItem from './CommentItem.vue';

type CommentUser = {
    id: number;
    name: string;
    username?: string | null;
    avatar_url?: string | null;
};

type Comment = {
    id: number;
    body: string;
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
    blogPostSlug: string;
    blogAuthorId: number;
    comments: Comment[];
    commentsPagination?: {
        next_page_url?: string | null;
        links?: any[];
    };
    commentsEnabled: boolean;
}>();

const page = usePage();

const authUser = computed(() =>
    (page.props.auth as any)?.user ?? null,
);

const body = ref('');
const processing = ref(false);

function submitComment(): void {
    if (!body.value.trim()) {
        return;
    }

    processing.value = true;

    router.post(
        `/blog/${props.blogPostSlug}/comments`,
        {
            body: body.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                body.value = '';
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <section class="mt-10 border-t sm:mt-14 border-stone-200 pt-10 dark:border-stone-800">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[color-mix(in_srgb,var(--brand-accent)_12%,transparent)] text-[var(--brand-accent)]">
                <MessageCircle class="h-5 w-5" />
            </div>

            <div>
                <h2 class="text-2xl font-semibold tracking-tight">
                    Comments
                </h2>

                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Join the discussion with other members.
                </p>
            </div>
        </div>

        <div
            v-if="authUser && commentsEnabled"
            class="mt-6 rounded-2xl border border-stone-200 bg-white p-4 sm:mt-7 sm:rounded-3xl sm:p-5 dark:border-stone-800 dark:bg-stone-900"
        >
            <label
                for="new-comment"
                class="text-sm font-semibold"
            >
                Add your comment
            </label>

            <textarea
                id="new-comment"
                v-model="body"
                rows="5"
                placeholder="Write a thoughtful comment..."
                class="mt-3 w-full resize-y rounded-2xl border border-stone-300 bg-transparent px-4 py-3 text-sm outline-none focus:border-[var(--brand-accent)] dark:border-stone-700"
            />

            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-stone-500 dark:text-stone-400">
                    Supports basic Markdown formatting.
                </p>

                <button
                    type="button"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full sm:w-auto bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white disabled:opacity-50"
                    :disabled="processing || !body.trim()"
                    @click="submitComment"
                >
                    <Send class="h-4 w-4" />
                    {{ processing ? 'Posting...' : 'Post Comment' }}
                </button>
            </div>
        </div>

        <div
            v-else-if="authUser && !commentsEnabled"
            class="mt-7 rounded-2xl border border-stone-200 bg-stone-100 p-4 text-sm text-stone-600 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-400"
        >
            Comments are closed for this article.
        </div>

        <div
            v-else
            class="mt-7 rounded-2xl border border-stone-200 bg-stone-100 p-4 text-sm text-stone-600 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-400"
        >
            Please log in to post a comment. Guests can read the discussion.
        </div>

        <div
            v-if="comments.length"
            class="mt-8 space-y-6"
        >
            <CommentItem
                v-for="comment in comments"
                :key="comment.id"
                :comment="comment"
                :blog-post-slug="blogPostSlug"
                :blog-author-id="blogAuthorId"
                :auth-user="authUser"
            />
        </div>

        <div
            v-else
            class="mt-8 rounded-2xl border border-dashed border-stone-300 p-8 text-center text-sm text-stone-500 dark:border-stone-700 dark:text-stone-400"
        >
            No comments yet. Be the first to start the conversation.
        </div>

        <div
            v-if="commentsPagination?.next_page_url"
            class="mt-7 flex justify-center"
        >
            <button
                type="button"
                class="inline-flex h-11 items-center rounded-full border border-stone-300 px-5 text-sm font-semibold dark:border-stone-700"
                @click="
                    router.visit(
                        commentsPagination.next_page_url,
                        {
                            preserveScroll: true,
                            preserveState: true,
                            only: ['comments'],
                        },
                    )
                "
            >
                Load More Comments
            </button>
        </div>
    </section>
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CommentItem from './CommentItem.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';

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

const authUser = computed(() => (page.props.auth as any)?.user ?? null);

const body = ref('');
const processing = ref(false);

function submitComment() {
    if (!body.value.trim()) return;

    processing.value = true;

    router.post(
        `/blog/${props.blogPostSlug}/comments`,
        { body: body.value },
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
    <section class="mt-12 rounded-xl border bg-card p-6 shadow-sm">
        <div class="mb-6">
            <h2 class="text-2xl font-semibold tracking-tight">
                Comments ({{ comments.length }})
            </h2>

            <p class="mt-1 text-sm text-muted-foreground">
                Join the discussion with other members.
            </p>
        </div>

        <div v-if="authUser && commentsEnabled" class="mb-8 space-y-3">
            <Textarea
                v-model="body"
                rows="4"
                placeholder="Write a comment..."
                class="resize-none"
            />

            <div class="mt-2 text-xs text-muted-foreground">
                Supports Markdown:
                <code>**bold**</code>,
                <code>*italic*</code>,
                <code>- lists</code>,
                <code>[links](https://...)</code>
            </div>

            <div class="flex justify-end">
                <Button
                    type="button"
                    :disabled="processing || !body.trim()"
                    @click="submitComment"
                >
                    Post Comment
                </Button>
            </div>
        </div>

        <div
            v-else-if="authUser && !commentsEnabled"
            class="mb-8 rounded-lg border bg-muted/40 p-4 text-sm text-muted-foreground"
        >
            Comments are closed for this article.
        </div>

        <div v-else class="mb-8 rounded-lg border bg-muted/40 p-4 text-sm text-muted-foreground">
            Please log in to post a comment. Guests can read the discussion.
        </div>

        <div v-if="comments.length" class="space-y-6">
            <CommentItem
                v-for="comment in comments"
                :key="comment.id"
                :comment="comment"
                :blog-post-slug="blogPostSlug"
                :blog-author-id="blogAuthorId"
                :auth-user="authUser"
            />
        </div>

        <div v-else class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
            No comments yet. Be the first to start the conversation.
        </div>

        <div
            v-if="commentsPagination?.next_page_url"
            class="mt-6 flex justify-center"
        >
            <Button
                type="button"
                variant="outline"
                @click="router.visit(commentsPagination.next_page_url, {
                    preserveScroll: true,
                    preserveState: true,
                    only: ['comments'],
                })"
            >
                Load More Comments
            </Button>
        </div>

    </section>
</template>
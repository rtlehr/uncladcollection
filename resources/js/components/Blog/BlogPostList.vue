<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import SidebarCard from '@/Components/Shared/SidebarCard.vue';

import type { BlogPost } from '@/types/blog';

import { formatDate } from '@/lib/formatDate';

defineProps<{
    title: string;
    posts: BlogPost[];
}>();
</script>

<template>
    <SidebarCard
        v-if="posts.length"
        :title="title"
    >
        <div class="space-y-4">
            <Link
                v-for="post in posts"
                :key="post.id"
                :href="`/blog/${post.slug}`"
                class="block group"
            >
                <div
                    class="text-sm font-semibold leading-5 group-hover:text-primary"
                >
                    {{ post.title }}
                </div>

                <div class="mt-1 text-xs text-muted-foreground">
                    {{ formatDate(post.published_at) }}
                </div>
            </Link>
        </div>
    </SidebarCard>
</template>
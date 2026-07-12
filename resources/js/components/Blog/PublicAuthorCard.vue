<script setup lang="ts">
import { ExternalLink, UserRound } from '@lucide/vue';

import type { Author } from '@/types/blog';

defineProps<{
    author: Author | null;
}>();
</script>

<template>
    <aside class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]">
            About the author
        </p>

        <div class="mt-4 flex items-center gap-4">
            <img
                v-if="author?.avatar_url"
                :src="author.avatar_url"
                :alt="author.name"
                class="h-14 w-14 rounded-full object-cover"
            />

            <div
                v-else
                class="flex h-14 w-14 items-center justify-center rounded-full bg-stone-200 dark:bg-stone-800"
            >
                <UserRound class="h-6 w-6 text-stone-500" />
            </div>

            <div>
                <div class="text-lg font-semibold">
                    {{ author?.name ?? 'Unclad Collection' }}
                </div>

                <div
                    v-if="author?.author_title"
                    class="text-sm text-stone-500 dark:text-stone-400"
                >
                    {{ author.author_title }}
                </div>
            </div>
        </div>

        <p class="mt-4 text-sm leading-7 text-stone-600 dark:text-stone-400">
            {{
                author?.author_bio
                ?? 'Contributor to Unclad Collection, sharing articles, photography, and resources for the nudist and naturist community.'
            }}
        </p>

        <a
            v-if="author?.author_website_url"
            :href="author.author_website_url"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-[var(--brand-accent)]"
        >
            Visit Author Website
            <ExternalLink class="h-4 w-4" />
        </a>
    </aside>
</template>

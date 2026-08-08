<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    Clock3,
} from '@lucide/vue';

import PerformanceImage from '@/components/Public/PerformanceImage.vue';

import { blogCardImage } from '@/lib/contentImages';
import { formatDate } from '@/lib/formatDate';
import { readingTime } from '@/lib/readingTime';
import type { BlogPost } from '@/types/blog';

withDefaults(
    defineProps<{
        post: BlogPost;
        variant?: 'standard' | 'horizontal' | 'hero';
    }>(),
    {
        variant: 'standard',
    },
);
</script>

<template>
    <Link
        :href="`/blog/${post.slug}`"
        prefetch="hover"
        :class="[
            'public-card group overflow-hidden rounded-3xl border border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900',
            variant === 'horizontal'
                ? 'grid md:grid-cols-[0.9fr_1.1fr]'
                : '',
            variant === 'hero'
                ? 'grid lg:grid-cols-[1.12fr_0.88fr]'
                : '',
        ]"
    >
        <div class="overflow-hidden bg-stone-200 dark:bg-stone-800">
            <PerformanceImage
                v-if="blogCardImage(post)"
                :src="blogCardImage(post)!"
                :alt="post.title"
                :loading="variant === 'hero' ? 'eager' : 'lazy'"
                :fetchpriority="variant === 'hero' ? 'high' : 'low'"
                sizes="(min-width: 1024px) 55vw, (min-width: 768px) 50vw, 100vw"
                :wrapper-class="
                    variant === 'standard'
                        ? 'aspect-[16/10]'
                        : variant === 'hero'
                            ? 'min-h-[320px]'
                            : 'min-h-[240px]'
                "
                image-class="public-image-zoom object-cover"
            />

            <div
                v-else
                :class="[
                    'flex items-center justify-center',
                    variant === 'standard'
                        ? 'aspect-[16/10]'
                        : 'min-h-[240px]',
                ]"
            >
                <BookOpen class="h-9 w-9 text-stone-400" />
            </div>
        </div>

        <div
            :class="[
                'flex flex-col',
                variant === 'hero'
                    ? 'justify-center p-5 sm:p-10'
                    : 'p-5 sm:p-6',
            ]"
        >
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span
                    v-if="post.is_featured"
                    class="rounded-full bg-[color-mix(in_srgb,var(--brand-accent)_13%,transparent)] px-2.5 py-1 font-semibold text-[var(--brand-accent)]"
                >
                    Featured
                </span>

                <span
                    v-for="category in post.categories.slice(0, 2)"
                    :key="category.id"
                    class="font-semibold uppercase tracking-wide text-[var(--brand-accent)]"
                >
                    {{ category.name }}
                </span>
            </div>

            <h2
                :class="[
                    'mt-4 font-semibold leading-tight tracking-tight transition group-hover:text-[var(--brand-accent)]',
                    variant === 'hero'
                        ? 'text-2xl sm:text-4xl'
                        : 'text-xl sm:text-2xl',
                ]"
            >
                {{ post.title }}
            </h2>

            <p
                v-if="post.excerpt"
                :class="[
                    'mt-4 text-stone-600 dark:text-stone-400',
                    variant === 'hero'
                        ? 'line-clamp-4 text-base leading-7'
                        : 'line-clamp-3 text-sm leading-6',
                ]"
            >
                {{ post.excerpt }}
            </p>

            <div class="mt-5 flex flex-col items-start gap-3 text-xs sm:mt-6 sm:flex-row sm:items-center sm:justify-between text-stone-500 dark:text-stone-400">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="font-medium text-stone-800 dark:text-stone-200">
                        {{ post.author?.name ?? 'Unclad Collection' }}
                    </span>

                    <span>
                        {{ formatDate(post.published_at) }}
                    </span>

                    <span class="inline-flex items-center gap-1">
                        <Clock3 class="h-3.5 w-3.5" />
                        {{ readingTime(post.content) }} min read
                    </span>
                </div>

                <span class="inline-flex items-center gap-1 font-semibold text-[var(--brand-accent)]">
                    Read
                    <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                </span>
            </div>
        </div>
    </Link>
</template>

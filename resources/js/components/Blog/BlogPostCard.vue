<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import { blogCardImage } from '@/lib/contentImages';
import { formatDate } from '@/lib/formatDate';
import type { BlogPost } from '@/types/blog';


withDefaults(
    defineProps<{
        post: BlogPost;
        variant?: 'grid' | 'featured';
        showAuthor?: boolean;
        showExcerpt?: boolean;
        showFeaturedBadge?: boolean;
    }>(),
    {
        variant: 'grid',
        showAuthor: true,
        showExcerpt: true,
        showFeaturedBadge: true,
    },
);
</script>

<template>
    <Link
        :href="`/blog/${post.slug}`"
        :class="[
            'group overflow-hidden rounded-xl border bg-card shadow-sm transition hover:shadow-md',
            variant === 'grid'
                ? 'hover:-translate-y-1'
                : 'grid sm:grid-cols-3',
        ]"
    >
        <div
            :class="[
                'overflow-hidden bg-muted',
                variant === 'featured' ? 'sm:col-span-1' : '',
            ]"
        >
            <img
                        loading="lazy"
                        decoding="async"
                v-if="blogCardImage(post)"
                :src="blogCardImage(post)!"
                :alt="post.title"
                :class="[
                    'w-full object-cover transition duration-300 group-hover:scale-105',
                    variant === 'grid'
                        ? 'aspect-[16/9]'
                        : 'aspect-[4/3] h-full',
                ]"
            />

            <div
                v-else
                :class="[
                    'flex items-center justify-center bg-muted text-sm text-muted-foreground',
                    variant === 'grid'
                        ? 'aspect-[16/9]'
                        : 'aspect-[4/3] h-full',
                ]"
            >
                No image
            </div>
        </div>

        <div
            :class="[
                'p-5',
                variant === 'featured' ? 'sm:col-span-2' : '',
            ]"
        >
            <div class="mb-3 flex flex-wrap gap-2">
                <span
                    v-if="showFeaturedBadge && post.is_featured"
                    class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary"
                >
                    Featured
                </span>

                <span
                    v-for="category in post.categories.slice(0, 2)"
                    :key="category.id"
                    class="rounded-full bg-muted px-2.5 py-1 text-xs"
                >
                    {{ category.name }}
                </span>
            </div>

            <h3
                :class="[
                    'font-bold group-hover:text-primary',
                    variant === 'grid'
                        ? 'line-clamp-2 text-xl'
                        : 'line-clamp-2 text-2xl',
                ]"
            >
                {{ post.title }}
            </h3>

            <p
                v-if="showExcerpt && post.excerpt"
                :class="[
                    'text-muted-foreground',
                    variant === 'grid'
                        ? 'mt-3 line-clamp-3 text-sm leading-6'
                        : 'mt-2 line-clamp-2 text-sm',
                ]"
            >
                {{ post.excerpt }}
            </p>

            <div
                :class="[
                    'text-xs text-muted-foreground',
                    variant === 'grid'
                        ? 'mt-5 flex items-center justify-between'
                        : 'mt-4',
                ]"
            >
                <span v-if="showAuthor">
                    {{ post.author?.name ?? 'Unclad Collection' }}
                </span>

                <span>
                    {{ formatDate(post.published_at) }}
                </span>
            </div>
        </div>
    </Link>
</template>
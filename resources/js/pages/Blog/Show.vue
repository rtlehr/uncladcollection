<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Clock3,
    Eye,
} from '@lucide/vue';
import {
    computed,
    onMounted,
    ref,
    watch,
} from 'vue';

import ArticleNavigation from '@/components/Blog/ArticleNavigation.vue';
import ArticleShareActions from '@/components/Blog/ArticleShareActions.vue';
import ArticleTableOfContents from '@/components/Blog/ArticleTableOfContents.vue';
import PublicArticleCard from '@/components/Blog/PublicArticleCard.vue';
import PublicAuthorCard from '@/components/Blog/PublicAuthorCard.vue';
import ReadingProgress from '@/components/Blog/ReadingProgress.vue';
import CommentSection from '@/components/comments/CommentSection.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';

import type {
    BlogNavigationPost,
    BlogPost,
} from '@/types/blog';

import { contentImage } from '@/lib/contentImages';
import { formatDate } from '@/lib/formatDate';
import { readingTime as calculateReadingTime } from '@/lib/readingTime';

const props = defineProps<{
    blogPost: BlogPost;
    relatedPosts: BlogPost[];
    authorPosts: BlogPost[];
    previousPost: BlogNavigationPost | null;
    nextPost: BlogNavigationPost | null;
    comments: {
        data: any[];
        links: any[];
        next_page_url?: string | null;
    };
}>();

const enhancedContent = ref('');
const tableOfContents = ref<Array<{
    id: string;
    text: string;
    level: number;
}>>([]);

const articleImage = computed(() =>
    contentImage(props.blogPost),
);

const readingTime = computed(() =>
    calculateReadingTime(props.blogPost.content),
);

const commentsEnabled = computed(() =>
    props.blogPost.comments_enabled ?? true,
);

const commentsVisible = computed(() =>
    props.blogPost.comments_visible ?? true,
);

const metaTitle = computed(() =>
    props.blogPost.seo_title || props.blogPost.title,
);

const metaDescription = computed(() =>
    props.blogPost.seo_description
    || props.blogPost.excerpt
    || '',
);

const arrowIconSvg = `
<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
viewBox="0 0 24 24" fill="none" stroke="currentColor"
stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round">
<path d="M7 17L17 7"/><path d="M7 7h10v10"/></svg>
`;

function slugifyHeading(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function buildEnhancedContent(): void {
    const html = props.blogPost.content ?? '';

    if (!html) {
        enhancedContent.value = '';
        tableOfContents.value = [];
        return;
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');

    const headings: Array<{
        id: string;
        text: string;
        level: number;
    }> = [];

    doc.querySelectorAll('h2, h3').forEach((heading, index) => {
        const text = heading.textContent?.trim() ?? '';

        if (!text) {
            return;
        }

        const id = heading.id
            || `${slugifyHeading(text) || 'section'}-${index + 1}`;

        heading.id = id;

        headings.push({
            id,
            text,
            level: Number(heading.tagName.substring(1)),
        });
    });

    doc.querySelectorAll('img[data-image-id]').forEach((img) => {
        const imageId = img.getAttribute('data-image-id');
        const slug = img.getAttribute('data-image-slug');
        const photographer = img.getAttribute('data-photographer');
        const publicUrl = img.getAttribute('data-public-url');
        const title = img.getAttribute('alt') || 'View image';

        const originalClass = img
            .getAttribute('class')
            ?.replace('ProseMirror-selectednode', '')
            .trim() ?? '';

        const figure = doc.createElement('figure');
        figure.className = `uc-media-card ${originalClass}`.trim();

        if (imageId) figure.setAttribute('data-image-id', imageId);
        if (slug) figure.setAttribute('data-image-slug', slug);
        if (publicUrl) figure.setAttribute('data-public-url', publicUrl);

        const clonedImg = img.cloneNode(true) as HTMLImageElement;
        clonedImg.className = 'uc-media-card-image';
        clonedImg.removeAttribute('contenteditable');
        clonedImg.removeAttribute('draggable');

        const caption = doc.createElement('figcaption');
        caption.className = 'uc-media-card-caption';

        caption.innerHTML = `
            <div class="uc-media-card-title">${title}</div>
            ${
                photographer
                    ? `
                        <div class="uc-media-card-credit-label">Photography by</div>
                        <div class="uc-media-card-credit-name">${photographer}</div>
                    `
                    : ''
            }
        `;

        const imageContainer = doc.createElement('div');
        imageContainer.className = 'uc-media-card-image-container';
        imageContainer.appendChild(clonedImg);

        const icon = doc.createElement('div');
        icon.className = 'uc-media-card-icon';
        icon.innerHTML = arrowIconSvg;
        imageContainer.appendChild(icon);

        figure.appendChild(imageContainer);
        figure.appendChild(caption);

        if (publicUrl) {
            const wrapper = doc.createElement('a');
            wrapper.href = publicUrl;
            wrapper.className = `uc-media-card-wrapper ${originalClass}`.trim();
            wrapper.setAttribute('aria-label', `View ${title}`);
            wrapper.appendChild(figure);
            img.replaceWith(wrapper);
        } else {
            img.replaceWith(figure);
        }
    });

    tableOfContents.value = headings;
    enhancedContent.value = doc.body.innerHTML;
}

onMounted(buildEnhancedContent);

watch(
    () => props.blogPost.content,
    buildEnhancedContent,
    { immediate: true },
);
</script>

<template>
    <Head>
        <title>{{ metaTitle }}</title>

        <meta
            v-if="metaDescription"
            name="description"
            :content="metaDescription"
        />

        <meta property="og:type" content="article" />
        <meta property="og:title" :content="metaTitle" />

        <meta
            v-if="metaDescription"
            property="og:description"
            :content="metaDescription"
        />

        <meta
            v-if="articleImage"
            property="og:image"
            :content="articleImage"
        />
    </Head>

    <PublicPageLayout>
        <ReadingProgress />

        <section class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
            <div class="mx-auto flex max-w-[1440px] items-center justify-between gap-4 px-5 py-4 sm:px-8 lg:px-12">
                <Link
                    href="/blog"
                    class="text-sm font-medium text-stone-500 transition hover:text-[var(--brand-accent)]"
                >
                    ← Back to Stories
                </Link>

                <ArticleShareActions :title="blogPost.title" />
            </div>
        </section>

        <article>
            <header class="mx-auto max-w-5xl px-5 py-12 text-center sm:px-8 sm:py-16 lg:px-12">
                <div
                    v-if="blogPost.categories.length"
                    class="flex flex-wrap justify-center gap-2"
                >
                    <Link
                        v-for="category in blogPost.categories"
                        :key="category.id"
                        :href="`/blog?category_id=${category.id}`"
                        class="rounded-full bg-[color-mix(in_srgb,var(--brand-accent)_12%,transparent)] px-3 py-1.5 text-xs font-semibold text-[var(--brand-accent)]"
                    >
                        {{ category.name }}
                    </Link>
                </div>

                <h1 class="mx-auto mt-6 max-w-4xl text-4xl font-semibold leading-[1.08] tracking-[-0.04em] sm:text-6xl">
                    {{ blogPost.title }}
                </h1>

                <p
                    v-if="blogPost.excerpt"
                    class="mx-auto mt-6 max-w-3xl text-lg leading-8 text-stone-600 dark:text-stone-300"
                >
                    {{ blogPost.excerpt }}
                </p>

                <div class="mt-7 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm text-stone-500 dark:text-stone-400">
                    <span class="font-medium text-stone-800 dark:text-stone-200">
                        {{ blogPost.author?.name ?? 'Unclad Collection' }}
                    </span>

                    <span>
                        {{ formatDate(blogPost.published_at) }}
                    </span>

                    <span class="inline-flex items-center gap-1">
                        <Clock3 class="h-4 w-4" />
                        {{ readingTime }} min read
                    </span>

                    <span class="inline-flex items-center gap-1">
                        <Eye class="h-4 w-4" />
                        {{ blogPost.views_count.toLocaleString() }} views
                    </span>
                </div>
            </header>

            <section
                v-if="articleImage"
                class="mx-auto max-w-[1320px] px-5 sm:px-8 lg:px-12"
            >
                <div class="overflow-hidden rounded-[2rem] border border-stone-200 bg-stone-200 shadow-xl dark:border-stone-800 dark:bg-stone-800">
                    <img
                        :src="articleImage"
                        :alt="blogPost.title"
                        fetchpriority="high"
                        class="aspect-[16/7] w-full object-cover"
                    />
                </div>
            </section>

            <section class="mx-auto max-w-[1320px] px-5 py-12 sm:px-8 lg:px-12 lg:py-16">
                <div class="grid gap-10 lg:grid-cols-[minmax(0,760px)_320px] lg:justify-center">
                    <div class="min-w-0">
                        <div
                            id="article-content"
                            class="blog-content prose prose-lg prose-stone max-w-none prose-headings:scroll-mt-24 prose-headings:tracking-tight prose-h2:mt-14 prose-h2:text-3xl prose-h3:mt-10 prose-h3:text-2xl prose-p:leading-8 prose-a:text-[var(--brand-accent)] prose-blockquote:border-[var(--brand-accent)] dark:prose-invert"
                            v-html="enhancedContent"
                        />

                        <div class="mt-12 border-t border-stone-200 pt-8 dark:border-stone-800">
                            <ArticleShareActions :title="blogPost.title" />
                        </div>

                        <div class="mt-10">
                            <ArticleNavigation
                                :previous-post="previousPost"
                                :next-post="nextPost"
                            />
                        </div>

                        <CommentSection
                            v-if="commentsVisible"
                            :blog-post-slug="blogPost.slug"
                            :blog-author-id="blogPost.user_id"
                            :comments="comments.data"
                            :comments-pagination="comments"
                            :comments-enabled="commentsEnabled"
                        />
                    </div>

                    <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start">
                        <ArticleTableOfContents :items="tableOfContents" />

                        <PublicAuthorCard :author="blogPost.author" />

                        <div
                            v-if="blogPost.tags.length"
                            class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900"
                        >
                            <h2 class="font-semibold">
                                Topics
                            </h2>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <Link
                                    v-for="tag in blogPost.tags"
                                    :key="tag.id"
                                    :href="`/blog?tag_id=${tag.id}`"
                                    class="rounded-full border border-stone-300 px-3 py-1.5 text-xs font-medium transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] dark:border-stone-700"
                                >
                                    #{{ tag.name }}
                                </Link>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>
        </article>

        <section
            v-if="relatedPosts.length"
            class="border-t border-stone-200 bg-white py-16 dark:border-stone-800 dark:bg-stone-900"
        >
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
                <div class="mb-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">
                        Keep reading
                    </p>

                    <h2 class="mt-3 text-3xl font-semibold tracking-tight">
                        Related Articles
                    </h2>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    <PublicArticleCard
                        v-for="post in relatedPosts"
                        :key="post.id"
                        :post="post"
                    />
                </div>
            </div>
        </section>

        <section
            v-if="authorPosts.length"
            class="border-t border-stone-200 py-16 dark:border-stone-800"
        >
            <div class="mx-auto max-w-[1440px] px-5 sm:px-8 lg:px-12">
                <div class="mb-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">
                        More from this contributor
                    </p>

                    <h2 class="mt-3 text-3xl font-semibold tracking-tight">
                        More by {{ blogPost.author?.name ?? 'Unclad Collection' }}
                    </h2>
                </div>

                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <PublicArticleCard
                        v-for="post in authorPosts"
                        :key="post.id"
                        :post="post"
                    />
                </div>
            </div>
        </section>
    </PublicPageLayout>
</template>

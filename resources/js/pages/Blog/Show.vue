<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import CommentSection from '@/components/comments/CommentSection.vue';

interface Author {
    id: number;
    name: string;

    author_title?: string | null;
    author_bio?: string | null;
    author_website_url?: string | null;

    avatar_url?: string | null;
}

interface Category {
    id: number;
    name: string;
    slug?: string;
}

interface Tag {
    id: number;
    name: string;
    slug?: string;
}

interface BlogPost {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string | null;

    featured_image_url: string | null;
    header_image_url: string | null;
    icon_image_url: string | null;

    published_at: string | null;
    views_count: number;

    seo_title?: string | null;
    seo_description?: string | null;

    author: Author | null;
    categories: Category[];
    tags: Tag[];
}

const arrowIconSvg = `
<svg
    xmlns="http://www.w3.org/2000/svg"
    width="18"
    height="18"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2.25"
    stroke-linecap="round"
    stroke-linejoin="round"
>
    <path d="M7 17L17 7"/>
    <path d="M7 7h10v10"/>
</svg>
`;

const props = defineProps<{
    blogPost: BlogPost;
    relatedPosts: BlogPost[];
    authorPosts: BlogPost[];
    comments: any[];
}>();

const articleImage = computed(() => {
    return props.blogPost.header_image_url
        ?? props.blogPost.featured_image_url
        ?? props.blogPost.icon_image_url;
});

const authorAvatar = computed(() => {
    return (
        props.blogPost.author?.avatar_url ??
        props.blogPost.icon_image_url ??
        null
    );
});

const metaTitle = computed(() => {
    return props.blogPost.seo_title || props.blogPost.title;
});

const metaDescription = computed(() => {
    return props.blogPost.seo_description || props.blogPost.excerpt || '';
});

const readingTime = computed(() => {
    const text = (props.blogPost.content ?? '').replace(/<[^>]+>/g, ' ');
    const words = text.trim().split(/\s+/).filter(Boolean).length;
    const minutes = Math.max(1, Math.ceil(words / 220));

    return `${minutes} min read`;
});

function formatDate(date: string | null): string {
    if (!date) {
        return '';
    }

    return new Date(date).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function postImage(post: BlogPost): string | null {
    return post.featured_image_url ?? post.header_image_url ?? post.icon_image_url;
}

const enhancedContent = ref('');

function buildEnhancedContent() {
    const html = props.blogPost.content ?? '';

    if (!html) {
        enhancedContent.value = '';
        return;
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');

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

    enhancedContent.value = doc.body.innerHTML;
}

onMounted(() => {
    buildEnhancedContent();
});

watch(
    () => props.blogPost.content,
    () => {
        buildEnhancedContent();
    },
    { immediate: true }
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

    <div class="min-h-screen bg-background">
        <section class="border-b bg-muted/30">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <Link
                    href="/blog"
                    class="inline-flex items-center text-sm font-medium text-muted-foreground transition hover:text-foreground"
                >
                    ← Back to Blog
                </Link>
            </div>
        </section>

        <main>
            <article>
                <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-4xl text-center">
                        <div
                            v-if="blogPost.categories.length"
                            class="mb-5 flex flex-wrap justify-center gap-2"
                        >
                            <Link
                                v-for="category in blogPost.categories"
                                :key="category.id"
                                :href="`/blog?category_id=${category.id}`"
                                class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary transition hover:bg-primary hover:text-primary-foreground"
                            >
                                {{ category.name }}
                            </Link>
                        </div>

                        <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                            {{ blogPost.title }}
                        </h1>

                        <p
                            v-if="blogPost.excerpt"
                            class="mx-auto mt-6 max-w-3xl text-lg leading-8 text-muted-foreground"
                        >
                            {{ blogPost.excerpt }}
                        </p>

                        <div class="mt-8 flex flex-wrap items-center justify-center gap-4 text-sm text-muted-foreground">
                            <div class="flex items-center gap-3">
                                <img
                                    v-if="authorAvatar"
                                    :src="authorAvatar"
                                    :alt="blogPost.author?.name ?? blogPost.title"
                                    class="h-11 w-11 rounded-full object-cover ring-2 ring-background"
                                />

                                <div class="text-left">
                                    <div class="font-medium text-foreground">
                                        {{ blogPost.author?.name ?? 'Unclad Collection' }}
                                    </div>

                                    <div>
                                        {{ formatDate(blogPost.published_at) }}
                                    </div>
                                </div>
                            </div>

                            <span class="hidden sm:inline">•</span>

                            <span>{{ readingTime }}</span>

                            <span class="hidden sm:inline">•</span>

                            <span>{{ blogPost.views_count }} views</span>
                        </div>
                    </div>
                </section>

                <section
                    v-if="articleImage"
                    class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
                >
                    <div class="aspect-[4/1] w-full overflow-hidden rounded-2xl border bg-muted shadow-sm">
                        <img
                            :src="articleImage"
                            :alt="blogPost.title"
                            class="h-full w-full object-cover object-center"
                        />
                    </div>
                </section>

                <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    <div class="grid gap-10 lg:grid-cols-[1fr_280px]">
                        <div class="mx-auto w-full max-w-3xl">
                            <div
                                class="blog-content prose prose-lg prose-neutral max-w-none dark:prose-invert"
                                v-html="enhancedContent"
                            />

                            <CommentSection
                                :blog-post-slug="blogPost.slug"
                                :comments="comments"
                            />
                        </div>

                        <aside class="space-y-6">

                                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                                    <div class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                        About the Author
                                    </div>

                                    <div class="mt-4">
                                        <div class="text-lg font-bold">
                                            {{ blogPost.author?.name ?? 'Unclad Collection' }}
                                        </div>

                                        <div
                                            v-if="blogPost.author?.author_title"
                                            class="mt-1 text-sm font-medium text-muted-foreground"
                                        >
                                            {{ blogPost.author.author_title }}
                                        </div>

                                        <p class="mt-3 text-sm leading-6 text-muted-foreground">
                                            {{
                                                blogPost.author?.author_bio
                                                    ?? 'Contributor to Unclad Collection, sharing articles, photography, and resources for the nudist and naturist community.'
                                            }}
                                        </p>

                                        <a
                                            v-if="blogPost.author?.author_website_url"
                                            :href="blogPost.author.author_website_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="mt-4 inline-flex text-sm font-semibold text-primary hover:underline"
                                        >
                                            Author Website →
                                        </a>
                                    </div>
                                </div>

                                <div
                                    v-if="authorPosts.length"
                                    class="rounded-2xl border bg-card p-5 shadow-sm"
                                >
                                    <div class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                        More by {{ blogPost.author?.name ?? 'this author' }}
                                    </div>

                                    <div class="mt-4 space-y-4">
                                        <Link
                                            v-for="post in authorPosts"
                                            :key="post.id"
                                            :href="`/blog/${post.slug}`"
                                            class="block group"
                                        >
                                            <div class="text-sm font-semibold leading-5 group-hover:text-primary">
                                                {{ post.title }}
                                            </div>

                                            <div class="mt-1 text-xs text-muted-foreground">
                                                {{ formatDate(post.published_at) }}
                                            </div>
                                        </Link>
                                    </div>
                                </div>

                                <div
                                    v-if="relatedPosts.length"
                                    class="rounded-2xl border bg-card p-5 shadow-sm"
                                >
                                    <div class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                        Related Articles
                                    </div>

                                    <div class="mt-4 space-y-4">
                                        <Link
                                            v-for="post in relatedPosts"
                                            :key="post.id"
                                            :href="`/blog/${post.slug}`"
                                            class="block group"
                                        >
                                            <div class="text-sm font-semibold leading-5 group-hover:text-primary">
                                                {{ post.title }}
                                            </div>

                                            <div class="mt-1 text-xs text-muted-foreground">
                                                {{ formatDate(post.published_at) }}
                                            </div>
                                        </Link>
                                    </div>
                                </div>

                            <div
                                v-if="blogPost.categories.length"
                                class="rounded-2xl border bg-card p-5 shadow-sm"
                            >
                                <div class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Categories
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <Link
                                        v-for="category in blogPost.categories"
                                        :key="category.id"
                                        :href="`/blog?category_id=${category.id}`"
                                        class="rounded-full bg-muted px-3 py-1 text-xs font-medium transition hover:bg-primary hover:text-primary-foreground"
                                    >
                                        {{ category.name }}
                                    </Link>
                                </div>
                            </div>

                            <div
                                v-if="blogPost.categories.length"
                                class="rounded-2xl border bg-card p-5 shadow-sm"
                            >
                                <div class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Tags
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                     <Link
                                        v-for="tag in blogPost.tags"
                                        :key="tag.id"
                                        :href="`/blog?tag_id=${tag.id}`"
                                        class="rounded-full border px-3 py-1 text-sm transition hover:bg-muted"
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
                class="border-t bg-muted/20"
            >
                <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                    <div class="mb-8 flex items-end justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-primary">
                                Keep Reading
                            </p>

                            <h2 class="mt-2 text-3xl font-bold tracking-tight">
                                Related Articles
                            </h2>
                        </div>

                        <Link
                            href="/blog"
                            class="hidden text-sm font-medium text-primary hover:underline sm:inline"
                        >
                            View all articles →
                        </Link>
                    </div>

                    <div class="grid gap-6 md:grid-cols-3">
                        <Link
                            v-for="post in relatedPosts"
                            :key="post.id"
                            :href="`/blog/${post.slug}`"
                            class="group overflow-hidden rounded-xl border bg-card shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                        >
                            <div class="overflow-hidden bg-muted">
                                <img
                                    v-if="postImage(post)"
                                    :src="postImage(post)!"
                                    :alt="post.title"
                                    class="aspect-[16/9] w-full object-cover transition duration-300 group-hover:scale-105"
                                />

                                <div
                                    v-else
                                    class="flex aspect-[16/9] w-full items-center justify-center bg-muted text-sm text-muted-foreground"
                                >
                                    No image
                                </div>
                            </div>

                            <div class="p-5">
                                <div class="mb-3 flex flex-wrap gap-2">
                                    <span
                                        v-for="category in post.categories.slice(0, 2)"
                                        :key="category.id"
                                        class="rounded-full bg-muted px-2.5 py-1 text-xs"
                                    >
                                        {{ category.name }}
                                    </span>
                                </div>

                                <h3 class="line-clamp-2 text-xl font-bold group-hover:text-primary">
                                    {{ post.title }}
                                </h3>

                                <p
                                    v-if="post.excerpt"
                                    class="mt-3 line-clamp-3 text-sm leading-6 text-muted-foreground"
                                >
                                    {{ post.excerpt }}
                                </p>

                                <div class="mt-5 text-xs text-muted-foreground">
                                    {{ formatDate(post.published_at) }}
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
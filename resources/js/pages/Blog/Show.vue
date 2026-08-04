<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
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
import PublicAdPlacement from '@/components/Advertising/PublicAdPlacement.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import PublicSeoHead from '@/components/Public/PublicSeoHead.vue';
import StructuredData from '@/components/Public/StructuredData.vue';

import type {
    BlogNavigationPost,
    BlogPost,
} from '@/types/blog';

import { articleHeaderImage } from '@/lib/contentImages';
import { formatDate } from '@/lib/formatDate';
import { readingTime as calculateReadingTime } from '@/lib/readingTime';

const props = defineProps<{
    blogPost: BlogPost;
    publicKeywords: Array<{
        id: number | null;
        name: string;
        slug: string | null;
        is_assigned: boolean;
    }>;
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
    articleHeaderImage(props.blogPost),
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


function escapeHtml(value: unknown): string {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function formatMoney(cents: number | null, currency = 'USD'): string {
    if (cents === null) return 'View pricing';

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
    }).format(cents / 100);
}

async function buildEnhancedContent(): Promise<void> {
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

    doc.querySelectorAll('img').forEach((img) => {
        const imageId = img.getAttribute('data-image-id');
        const slug = img.getAttribute('data-image-slug');
        const photographer = img.getAttribute('data-photographer');
        const publicUrl = img.getAttribute('data-public-url');
        const assetTitle =
            img.getAttribute('data-asset-title')
            || img.getAttribute('alt')
            || 'Article image';
        const captionText = img.getAttribute('data-caption');
        const credit = img.getAttribute('data-credit');
        const showLicenseLink =
            img.getAttribute('data-show-license-link') === 'true';
        const clickToEnlarge =
            img.getAttribute('data-click-to-enlarge') === 'true';
        const borderStyle =
            img.getAttribute('data-border-style') || 'none';
        const shadowStyle =
            img.getAttribute('data-shadow-style') || 'none';
        const roundedStyle =
            img.getAttribute('data-rounded-style') || 'small';
        const spacingStyle =
            img.getAttribute('data-spacing-style') || 'normal';

        const shouldEnhance = Boolean(
            imageId
            || captionText
            || credit
            || photographer
            || publicUrl
            || clickToEnlarge
            || borderStyle !== 'none'
            || shadowStyle !== 'none'
            || roundedStyle !== 'small'
            || spacingStyle !== 'normal',
        );

        if (!shouldEnhance) {
            return;
        }

        const originalClass = img
            .getAttribute('class')
            ?.replace('ProseMirror-selectednode', '')
            .trim() ?? '';

        const figure = doc.createElement('figure');
        figure.className = [
            'uc-article-media',
            originalClass,
            `uc-media-border-${borderStyle}`,
            `uc-media-shadow-${shadowStyle}`,
            `uc-media-rounded-${roundedStyle}`,
            `uc-media-spacing-${spacingStyle}`,
        ].filter(Boolean).join(' ');

        if (imageId) figure.setAttribute('data-image-id', imageId);
        if (slug) figure.setAttribute('data-image-slug', slug);

        const clonedImg = img.cloneNode(true) as HTMLImageElement;
        clonedImg.className = 'uc-article-media-image';
        clonedImg.removeAttribute('contenteditable');
        clonedImg.removeAttribute('draggable');

        const imageContainer = doc.createElement('div');
        imageContainer.className = 'uc-article-media-image-container';

        if (clickToEnlarge) {
            const enlargeLink = doc.createElement('a');
            enlargeLink.href = clonedImg.src;
            enlargeLink.target = '_blank';
            enlargeLink.rel = 'noopener';
            enlargeLink.className = 'uc-article-media-enlarge';
            enlargeLink.setAttribute(
                'aria-label',
                `Open larger image: ${assetTitle}`,
            );
            enlargeLink.appendChild(clonedImg);
            imageContainer.appendChild(enlargeLink);
        } else {
            imageContainer.appendChild(clonedImg);
        }

        figure.appendChild(imageContainer);

        if (
            captionText
            || credit
            || photographer
            || (showLicenseLink && publicUrl)
        ) {
            const figcaption = doc.createElement('figcaption');
            figcaption.className = 'uc-article-media-caption';

            if (captionText) {
                const caption = doc.createElement('div');
                caption.className = 'uc-article-media-caption-text';
                caption.textContent = captionText;
                figcaption.appendChild(caption);
            }

            const attribution = credit || photographer;

            if (attribution) {
                const creditLine = doc.createElement('div');
                creditLine.className = 'uc-article-media-credit';
                creditLine.textContent = credit
                    || `Photography by ${photographer}`;
                figcaption.appendChild(creditLine);
            }

            if (showLicenseLink && publicUrl) {
                const licenseLink = doc.createElement('a');
                licenseLink.href = publicUrl;
                licenseLink.className = 'uc-article-media-license';
                licenseLink.textContent = 'License This Image';
                figcaption.appendChild(licenseLink);
            }

            figure.appendChild(figcaption);
        }

        img.replaceWith(figure);
    });


    const cardNodes = Array.from(
        doc.querySelectorAll<HTMLElement>(
            '[data-smart-asset-card="true"]',
        ),
    );

    await Promise.all(
        cardNodes.map(async (node) => {
            const slug = node.dataset.assetSlug;
            const layout = node.dataset.layout ?? 'standard';
            const heading = node.dataset.heading ?? '';
            const description = node.dataset.description ?? '';

            if (!slug) return;

            try {
                const response = await fetch(
                    `/assets/${encodeURIComponent(slug)}/card-data`,
                    { headers: { Accept: 'application/json' } },
                );

                if (!response.ok) {
                    throw new Error('Asset unavailable');
                }

                const data = await response.json();
                const asset = data.asset;

                const card = doc.createElement('article');
                card.className =
                    `uc-smart-asset-card uc-smart-asset-card-${layout}`;

                const safeHref = escapeHtml(asset.href);
                const safeLicenseHref = escapeHtml(
                    asset.license_href || `${asset.href}#purchase`,
                );
                const safeTitle = escapeHtml(heading || asset.title);
                const safeAssetTitle = escapeHtml(asset.title);
                const safeDescription = escapeHtml(description);
                const safePhotographer = escapeHtml(
                    asset.photographer || 'Unclad Collection',
                );
                const safeType = escapeHtml(asset.asset_type_label);

                const image = asset.preview_url
                    ? `<img src="${escapeHtml(asset.preview_url)}" alt="${safeAssetTitle}" loading="lazy" />`
                    : `<div class="uc-smart-asset-image-fallback">${safeType}</div>`;

                const formats = (asset.formats ?? [])
                    .slice(0, 8)
                    .map(
                        (format: string) =>
                            `<span class="uc-smart-asset-format">${escapeHtml(format)}</span>`,
                    )
                    .join('');

                const badges = (asset.badges ?? [])
                    .slice(0, 5)
                    .map(
                        (badge: string) =>
                            `<span class="uc-smart-asset-badge">${escapeHtml(badge)}</span>`,
                    )
                    .join('');

                const offerings = (asset.offerings ?? [])
                    .slice(0, layout === 'compact' ? 2 : 3)
                    .map((offering: any) => {
                        const licenseName = escapeHtml(
                            offering.license_type?.name || offering.name,
                        );
                        const offeringPrice = formatMoney(
                            offering.price_cents,
                            offering.currency || asset.currency || 'USD',
                        );
                        const offeringFormats = (offering.formats ?? [])
                            .slice(0, 4)
                            .map((format: string) => escapeHtml(format))
                            .join(' · ');

                        return `
                            <li>
                                <span>
                                    <strong>${licenseName}</strong>
                                    ${offeringFormats ? `<small>${offeringFormats}</small>` : ''}
                                </span>
                                <b>${escapeHtml(offeringPrice)}</b>
                            </li>
                        `;
                    })
                    .join('');

                const startingPrice = formatMoney(
                    asset.starting_price_cents,
                    asset.currency ?? 'USD',
                );
                const priceRange =
                    asset.highest_price_cents !== null
                    && asset.highest_price_cents !== asset.starting_price_cents
                        ? `${startingPrice}–${formatMoney(asset.highest_price_cents, asset.currency ?? 'USD')}`
                        : startingPrice;

                card.innerHTML = `
                    <a class="uc-smart-asset-image" href="${safeHref}" aria-label="View ${safeAssetTitle} in the marketplace">
                        ${image}
                        ${badges ? `<span class="uc-smart-asset-badges">${badges}</span>` : ''}
                    </a>
                    <div class="uc-smart-asset-body">
                        <div class="uc-smart-asset-eyebrow"><span>${safeType}</span><span class="uc-smart-asset-live-label">Live marketplace listing</span></div>
                        <h3><a href="${safeHref}">${safeTitle}</a></h3>
                        ${safeDescription ? `<p class="uc-smart-asset-description">${safeDescription}</p>` : ''}
                        <div class="uc-smart-asset-meta">By ${safePhotographer}</div>
                        ${formats ? `<div class="uc-smart-asset-formats">${formats}</div>` : ''}
                        ${offerings ? `
                            <div class="uc-smart-asset-license-summary">
                                <span>Available licenses</span>
                                <ul>${offerings}</ul>
                            </div>
                        ` : ''}
                        <div class="uc-smart-asset-footer">
                            <div class="uc-smart-asset-price">
                                <span>${asset.offerings_count > 1 ? 'Starting at' : 'Price'}</span>
                                <strong>${escapeHtml(priceRange)}</strong>
                            </div>
                            <div class="uc-smart-asset-actions">
                                <a class="uc-smart-asset-action-secondary" href="${safeHref}" aria-label="View details for ${safeAssetTitle}">View details</a>
                                <a class="uc-smart-asset-action-primary" href="${safeLicenseHref}" aria-label="License ${safeAssetTitle}">License asset <span aria-hidden="true">→</span></a>
                            </div>
                        </div>
                    </div>
                `;

                node.replaceWith(card);
            } catch {
                node.innerHTML =
                    '<p>This Asset is currently unavailable.</p>';
                node.className = 'uc-smart-asset-card-unavailable';
            }
        }),
    );

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
    <PublicSeoHead
        :title="metaTitle"
        :description="metaDescription"
        :image="articleImage"
        :canonical-path="`/blog/${blogPost.slug}`"
        type="article"
        :published-time="blogPost.published_at"
        :author-name="blogPost.author?.name ?? 'Unclad Collection'"
/>


    <StructuredData

        :breadcrumbs="[
            { name: 'Home', url: '/' },
            { name: 'Stories', url: '/blog' },
            { name: blogPost.title, url: `/blog/${blogPost.slug}` },
        ]"

        :image="articleImage"

    />

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
            <header class="mx-auto max-w-5xl px-4 py-10 sm:px-8 sm:py-12 text-center sm:px-8 sm:py-16 lg:px-12">
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

                <h1 class="mx-auto mt-6 max-w-4xl break-words text-3xl font-semibold sm:text-4xl leading-[1.08] tracking-[-0.04em] sm:text-6xl">
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

            <section class="mx-auto max-w-[1320px] px-4 py-10 sm:px-8 sm:py-12 sm:px-8 lg:px-12 lg:py-16">
                <div class="grid gap-8 lg:gap-10 lg:grid-cols-[minmax(0,760px)_320px] lg:justify-center">
                    <div class="min-w-0">
                        <div
                            id="article-content"
                            class="blog-content prose prose-lg prose-stone max-w-none prose-headings:scroll-mt-24 prose-headings:tracking-tight prose-h2:mt-14 prose-h2:text-3xl prose-h3:mt-10 prose-h3:text-2xl prose-p:leading-8 prose-a:text-[var(--brand-accent)] prose-blockquote:border-[var(--brand-accent)] dark:prose-invert"
                            v-html="enhancedContent"
                        />

                        <PublicAdPlacement placement="blog-article-after-content" class="mt-12" />

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
                            v-if="publicKeywords.length"
                            class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900"
                        >
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]">
                                Article keywords
                            </p>

                            <h2 class="mt-2 font-semibold">
                                Topics in this article
                            </h2>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <template
                                    v-for="keyword in publicKeywords"
                                    :key="`${keyword.id ?? 'generated'}-${keyword.name}`"
                                >
                                    <Link
                                        v-if="keyword.id"
                                        :href="`/blog?tag_id=${keyword.id}`"
                                        class="rounded-full border border-stone-300 px-3 py-1.5 text-xs font-medium transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] dark:border-stone-700"
                                    >
                                        #{{ keyword.name }}
                                    </Link>

                                    <span
                                        v-else
                                        class="rounded-full border border-stone-300 px-3 py-1.5 text-xs font-medium text-stone-700 dark:border-stone-700 dark:text-stone-300"
                                    >
                                        #{{ keyword.name }}
                                    </span>
                                </template>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>
        </article>

        <section
            v-if="relatedPosts.length"
            class="public-deferred-section border-t border-stone-200 bg-white py-16 dark:border-stone-800 dark:bg-stone-900"
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
            class="public-deferred-section border-t border-stone-200 py-16 dark:border-stone-800"
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

<style scoped>
.blog-content {
    color: inherit;
    font-size: 1.0625rem;
    line-height: 1.85;
}

.blog-content :deep(p) {
    margin: 0 0 1.5rem;
    line-height: 1.85;
}

.blog-content :deep(p:last-child) {
    margin-bottom: 0;
}

.blog-content :deep(h2) {
    margin-top: 3.5rem;
    margin-bottom: 1.25rem;
    font-size: 1.875rem;
    font-weight: 700;
    line-height: 1.2;
    letter-spacing: -0.025em;
}

.blog-content :deep(h3) {
    margin-top: 2.5rem;
    margin-bottom: 1rem;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.3;
    letter-spacing: -0.02em;
}

.blog-content :deep(h4) {
    margin-top: 2rem;
    margin-bottom: 0.875rem;
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.35;
}

.blog-content :deep(ul),
.blog-content :deep(ol) {
    margin: 0 0 1.5rem;
    padding-left: 1.75rem;
}

.blog-content :deep(ul) {
    list-style: disc;
}

.blog-content :deep(ol) {
    list-style: decimal;
}

.blog-content :deep(li) {
    margin: 0.45rem 0;
    padding-left: 0.25rem;
}

.blog-content :deep(blockquote) {
    margin: 2rem 0;
    border-left: 4px solid var(--brand-accent);
    padding: 0.25rem 0 0.25rem 1.25rem;
    font-style: italic;
}

.blog-content :deep(blockquote p) {
    margin-bottom: 0.75rem;
}

.blog-content :deep(a) {
    color: var(--brand-accent);
    text-decoration: underline;
    text-decoration-thickness: 1px;
    text-underline-offset: 3px;
}

.blog-content :deep(hr) {
    margin: 3rem 0;
    border: 0;
    border-top: 1px solid rgb(214 211 209);
}

.blog-content :deep(img),
.blog-content :deep(figure) {
    margin-top: 2rem;
    margin-bottom: 2rem;
}

.blog-content :deep(figcaption) {
    margin-top: 0.75rem;
    font-size: 0.875rem;
    line-height: 1.5;
}

.blog-content :deep(p:empty) {
    display: none;
}

@media (max-width: 640px) {
    .blog-content {
        font-size: 1rem;
        line-height: 1.75;
    }

    .blog-content :deep(p) {
        margin-bottom: 1.25rem;
        line-height: 1.75;
    }

    .blog-content :deep(h2) {
        margin-top: 2.75rem;
        font-size: 1.625rem;
    }

    .blog-content :deep(h3) {
        margin-top: 2.25rem;
        font-size: 1.375rem;
    }
}
</style>

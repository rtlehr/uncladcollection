<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import ContentSection from '@/components/Public/ContentSection.vue';
import PublicBreadcrumbs from '@/components/Public/PublicBreadcrumbs.vue';
import PublicCallout from '@/components/Public/PublicCallout.vue';
import PublicCTA from '@/components/Public/PublicCTA.vue';
import PublicFAQ from '@/components/Public/PublicFAQ.vue';
import PublicHero from '@/components/Public/PublicHero.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import PublicRelatedLinks from '@/components/Public/PublicRelatedLinks.vue';
import PublicSidebarCard from '@/components/Public/PublicSidebarCard.vue';

const breadcrumbs = [
    { label: 'Home', href: '/' },
    { label: 'Public Page Template' },
];

const faqItems = [
    {
        question: 'What is this page intended for?',
        answer: 'Use it for About, Licensing, FAQ, Contact, Privacy, Terms, Support, contributor information, and future CMS-driven content.',
    },
    {
        question: 'Can the sidebar be removed?',
        answer: 'Yes. A page can use a full-width article, narrow reading column, or a main-content and sidebar layout.',
    },
    {
        question: 'Does it use the Admin Settings colors?',
        answer: 'Yes. It reads the shared primary, secondary, and accent colors used by the homepage.',
    },
];

const relatedLinks = [
    {
        title: 'Browse Images',
        description: 'Explore natural, respectful photography from the image library.',
        href: '/images',
    },
    {
        title: 'Read Stories',
        description: 'Discover practical guidance and community perspectives.',
        href: '/blog',
    },
    {
        title: 'Create an Account',
        description: 'Save favorites and purchase licensed downloads.',
        href: '/register',
    },
];
</script>

<template>
    <Head title="Public Page Template" />

    <PublicPageLayout>
        <PublicHero
            eyebrow="Public content system"
            title="A flexible template for every inside page"
            description="Use this structure for About, Licensing, FAQ, Privacy, Terms, Support, contributor information, and other public content."
            compact
        >
            <template #breadcrumbs>
                <PublicBreadcrumbs :items="breadcrumbs" />
            </template>

            <template #actions>
                <Link
                    href="/images"
                    class="inline-flex h-12 items-center rounded-full bg-[var(--brand-primary)] px-6 text-sm font-semibold text-white"
                >
                    Browse Images
                </Link>

                <Link
                    href="/blog"
                    class="inline-flex h-12 items-center rounded-full border border-stone-300 bg-white px-6 text-sm font-semibold dark:border-stone-700 dark:bg-stone-900"
                >
                    Read Stories
                </Link>
            </template>
        </PublicHero>

        <ContentSection>
            <div class="grid gap-8 lg:gap-10 lg:grid-cols-[minmax(0,1fr)_320px]">
                <article class="min-w-0">
                    <div
                        id="overview"
                        class="prose prose-stone max-w-none prose-headings:tracking-tight prose-h2:mt-12 prose-h2:text-3xl prose-h3:text-2xl prose-p:leading-8 prose-a:text-[var(--brand-accent)] dark:prose-invert"
                    >
                        <h2>Designed for clear, readable public content</h2>

                        <p>
                            This placeholder content demonstrates the standard inside-page layout. Future pages can feel related to the homepage while supporting longer, more detailed information.
                        </p>

                        <p>
                            Public pages should be easy to scan, comfortable to read, and flexible enough to support text, images, lists, callouts, tables, questions, and related links.
                        </p>

                        <h3>What this system provides</h3>

                        <ul>
                            <li>A shared branded header and footer</li>
                            <li>Breadcrumb navigation</li>
                            <li>Flexible hero sections</li>
                            <li>Readable article typography</li>
                            <li>Optional sidebar content</li>
                            <li>Accessible callouts and FAQs</li>
                            <li>Reusable calls to action</li>
                        </ul>
                    </div>

                    <div id="content" class="mt-10 space-y-5">
                        <PublicCallout title="Helpful information" tone="info">
                            <p>
                                Use this for context, definitions, links, or details that help the reader understand the page.
                            </p>
                        </PublicCallout>

                        <PublicCallout title="A positive next step" tone="success">
                            <p>
                                Success callouts work well for membership benefits, approvals, or completed steps.
                            </p>
                        </PublicCallout>

                        <PublicCallout title="Important limitation" tone="warning">
                            <p>
                                Warning callouts should be reserved for legal requirements, licensing restrictions, or safety information.
                            </p>
                        </PublicCallout>
                    </div>

                    <figure class="mt-12 overflow-hidden rounded-3xl border border-stone-200 bg-stone-200 dark:border-stone-800 dark:bg-stone-800">
                        <div class="aspect-[16/7] bg-[linear-gradient(135deg,color-mix(in_srgb,var(--brand-secondary)_65%,white),color-mix(in_srgb,var(--brand-primary)_78%,black))]" />

                        <figcaption class="bg-white px-5 py-4 text-sm text-stone-600 dark:bg-stone-900 dark:text-stone-400">
                            Placeholder image area. Replace it with a real image and meaningful alternative text.
                        </figcaption>
                    </figure>

                    <blockquote class="mt-12 border-l-4 border-[var(--brand-accent)] pl-6 text-2xl font-medium leading-10">
                        “A consistent page system helps every part of the site feel intentional, trustworthy, and professionally designed.”
                    </blockquote>
                </article>

                <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start">
                    <PublicSidebarCard
                        title="On this page"
                        description="Example navigation for a longer content page."
                    >
                        <nav class="grid gap-3 text-sm">
                            <a href="#overview" class="hover:text-[var(--brand-accent)]">Overview</a>
                            <a href="#content" class="hover:text-[var(--brand-accent)]">Content elements</a>
                            <a href="#faq" class="hover:text-[var(--brand-accent)]">Frequently asked questions</a>
                        </nav>
                    </PublicSidebarCard>

                    <PublicSidebarCard
                        title="Need help?"
                        description="Use this card for contact information, downloads, related documents, or support links."
                    >
                        <a
                            href="mailto:info@uncladcollection.com"
                            class="text-sm font-semibold text-[var(--brand-accent)]"
                        >
                            Contact Unclad Collection
                        </a>
                    </PublicSidebarCard>
                </aside>
            </div>
        </ContentSection>

        <ContentSection
            id="faq"
            eyebrow="Common questions"
            title="Frequently asked questions"
            description="Suitable for help, licensing, contributor, policy, and support pages."
            tone="muted"
            narrow
        >
            <PublicFAQ :items="faqItems" />
        </ContentSection>

        <ContentSection
            eyebrow="Keep exploring"
            title="Related destinations"
            description="Guide visitors toward the next useful part of the site."
        >
            <PublicRelatedLinks :items="relatedLinks" />
        </ContentSection>

        <PublicCTA
            title="Ready to explore the collection?"
            description="Browse respectful imagery, read new stories, or create an account to save favorites and purchase licensed downloads."
            primary-label="Browse Images"
            primary-href="/images"
            secondary-label="Read Stories"
            secondary-href="/blog"
        />
    </PublicPageLayout>
</template>

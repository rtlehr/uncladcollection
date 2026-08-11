<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicAdPlacement from '@/components/Advertising/PublicAdPlacement.vue';
import PublicBreadcrumbs from '@/components/Public/PublicBreadcrumbs.vue';
import PublicFAQ from '@/components/Public/PublicFAQ.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { PublicPageRecord } from '@/types/publicPages';

type SectionMenuItem = {
    id: number;
    title: string;
    url: string;
};

const props = defineProps<{
    publicPage: PublicPageRecord;
    sectionMenu?: {
        parent: SectionMenuItem;
        children: SectionMenuItem[];
    } | null;
}>();

const user = (usePage().props.auth as any)?.user;
const sidebarAdAvailable = ref(false);
const hasSectionMenu = computed(() => Boolean(props.sectionMenu?.children?.length));
const sidebarVisible = computed(() => hasSectionMenu.value || sidebarAdAvailable.value);
const sectionMenuItems = computed<SectionMenuItem[]>(() =>
    props.sectionMenu
        ? [props.sectionMenu.parent, ...props.sectionMenu.children]
        : [],
);

const form = useForm({
    name: user?.name ?? '',
    email: user?.email ?? '',
    subject: '',
    topic: '',
    message: '',
});

function submitContact(): void {
    form.post(`/${props.publicPage.slug}/contact`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('subject', 'topic', 'message');
        },
    });
}
</script>

<template>
    <Head>
        <title>{{ publicPage.seo_title || publicPage.title }}</title>

        <meta
            v-if="publicPage.seo_description || publicPage.introduction"
            name="description"
            :content="
                publicPage.seo_description
                    || publicPage.introduction
                    || ''
            "
        />

        <link
            v-if="publicPage.canonical_url"
            rel="canonical"
            :href="publicPage.canonical_url"
        />
    </Head>

    <PublicPageLayout>
        <section class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
            <div class="mx-auto max-w-[1440px] px-5 py-4 sm:px-8 lg:px-12">
                <PublicBreadcrumbs
                    :items="[
                        {
                            label: 'Home',
                            href: '/',
                        },
                        ...(sectionMenu?.parent && sectionMenu.parent.id !== publicPage.id
                            ? [{ label: sectionMenu.parent.title, href: sectionMenu.parent.url }]
                            : []),
                        {
                            label: publicPage.title,
                        },
                    ]"
                />
            </div>
        </section>

        <article>
            <header class="mx-auto max-w-5xl px-4 py-10 text-center sm:px-8 sm:py-16 lg:px-12">
                <p
                    v-if="publicPage.eyebrow"
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]"
                >
                    {{ publicPage.eyebrow }}
                </p>

                <h1
                    class="mx-auto max-w-4xl break-words text-3xl font-semibold leading-[1.08] tracking-[-0.04em] sm:text-6xl"
                    :class="publicPage.eyebrow ? 'mt-6' : ''"
                >
                    {{ publicPage.title }}
                </h1>

                <p
                    v-if="publicPage.introduction"
                    class="mx-auto mt-6 max-w-3xl text-lg leading-8 text-stone-600 dark:text-stone-300"
                >
                    {{ publicPage.introduction }}
                </p>
            </header>

            <section
                v-if="publicPage.header_image_url"
                class="mx-auto max-w-[1320px] px-5 sm:px-8 lg:px-12"
            >
                <div class="overflow-hidden rounded-[2rem] border border-stone-200 bg-stone-200 shadow-xl dark:border-stone-800 dark:bg-stone-800">
                    <img
                        :src="publicPage.header_image_url"
                        :alt="publicPage.header_image_alt || publicPage.title"
                        fetchpriority="high"
                        class="aspect-[16/7] w-full object-cover"
                    />
                </div>
            </section>

            <section class="mx-auto max-w-[1320px] px-4 py-10 sm:px-8 sm:py-12 lg:px-12 lg:py-16">
                <div
                    class="grid gap-8 lg:justify-center lg:gap-10"
                    :class="
                        sidebarVisible
                            ? 'lg:grid-cols-[minmax(0,760px)_320px]'
                            : 'lg:grid-cols-1 lg:px-12'
                    "
                >
                    <div class="min-w-0">
                        <div
                            v-if="
                                publicPage.page_type === 'legal'
                                && (
                                    publicPage.legal_version
                                    || publicPage.effective_date
                                    || publicPage.revised_date
                                )
                            "
                            class="mb-8 flex flex-wrap gap-3 rounded-2xl border border-stone-200 bg-white p-4 text-sm text-stone-500 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:text-stone-400"
                        >
                            <span v-if="publicPage.legal_version">
                                Version {{ publicPage.legal_version }}
                            </span>

                            <span v-if="publicPage.effective_date">
                                Effective {{ publicPage.effective_date }}
                            </span>

                            <span v-if="publicPage.revised_date">
                                Revised {{ publicPage.revised_date }}
                            </span>
                        </div>

                        <div
                            v-if="publicPage.content"
                            class="blog-content prose prose-lg prose-stone max-w-none prose-headings:scroll-mt-24 prose-headings:tracking-tight prose-h2:mt-14 prose-h2:text-3xl prose-h3:mt-10 prose-h3:text-2xl prose-p:leading-8 prose-a:text-[var(--brand-accent)] prose-blockquote:border-[var(--brand-accent)] dark:prose-invert"
                            v-html="publicPage.content"
                        />

                        <div
                            v-if="
                                publicPage.page_type === 'faq'
                                && publicPage.faq_items?.length
                            "
                            class="mt-10"
                        >
                            <PublicFAQ :items="publicPage.faq_items" />
                        </div>

                        <form
                            v-if="publicPage.page_type === 'contact'"
                            class="mt-10 space-y-5 rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 sm:p-8"
                            @submit.prevent="submitContact"
                        >
                            <div class="grid gap-5 sm:grid-cols-2">
                                <label class="text-sm font-medium">
                                    Name

                                    <Input
                                        v-model="form.name"
                                        class="mt-2"
                                    />
                                </label>

                                <label class="text-sm font-medium">
                                    Email

                                    <Input
                                        v-model="form.email"
                                        type="email"
                                        class="mt-2"
                                    />
                                </label>
                            </div>

                            <label class="block text-sm font-medium">
                                Subject

                                <Input
                                    v-model="form.subject"
                                    class="mt-2"
                                />
                            </label>

                            <label class="block text-sm font-medium">
                                Topic

                                <Input
                                    v-model="form.topic"
                                    class="mt-2"
                                    placeholder="Optional"
                                />
                            </label>

                            <label class="block text-sm font-medium">
                                Message

                                <textarea
                                    v-model="form.message"
                                    rows="7"
                                    class="mt-2 w-full rounded-md border bg-background p-3"
                                />
                            </label>

                            <p
                                v-for="(error, field) in form.errors"
                                :key="field"
                                class="text-sm text-destructive"
                            >
                                {{ error }}
                            </p>

                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Sending...' : 'Send Message' }}
                            </Button>
                        </form>

                        <PublicAdPlacement
                            placement="public-page-after-content"
                            class="mt-12"
                        />
                    </div>

                    <aside
                        v-show="sidebarVisible"
                        class="space-y-5 lg:self-start"
                    >
                        <nav
                            v-if="hasSectionMenu"
                            class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-800 dark:bg-stone-900"
                            aria-label="Section navigation"
                        >
                            <div class="border-b border-stone-200 px-5 py-4 dark:border-stone-800">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500 dark:text-stone-400">
                                    In this section
                                </p>
                            </div>

                            <div class="p-2">
                                <Link
                                    v-for="(item, index) in sectionMenuItems"
                                    :key="item.id"
                                    :href="item.url"
                                    class="relative flex items-center rounded-xl px-4 py-3 text-sm transition-colors"
                                    :class="[
                                        item.id === publicPage.id
                                            ? 'bg-stone-100 font-semibold text-stone-950 dark:bg-stone-800 dark:text-white'
                                            : 'text-stone-600 hover:bg-stone-50 hover:text-stone-950 dark:text-stone-300 dark:hover:bg-stone-800/70 dark:hover:text-white',
                                        index === 0 ? 'mb-1 text-base' : '',
                                    ]"
                                    :aria-current="item.id === publicPage.id ? 'page' : undefined"
                                >
                                    <span
                                        v-if="item.id === publicPage.id"
                                        class="absolute inset-y-2 left-0 w-1 rounded-full bg-[var(--brand-accent)]"
                                    />
                                    <span :class="index > 0 ? 'pl-3' : ''">
                                        {{ item.title }}
                                    </span>
                                </Link>
                            </div>
                        </nav>

                        <PublicAdPlacement
                            placement="public-page-sidebar"
                            @availability="sidebarAdAvailable = $event"
                        />
                    </aside>
                </div>
            </section>
        </article>
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

.blog-content :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 1rem;
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

<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    LifeBuoy,
    LogIn,
    TicketCheck,
} from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import PublicBreadcrumbs from '@/components/Public/PublicBreadcrumbs.vue';
import PublicFAQ from '@/components/Public/PublicFAQ.vue';
import PublicHero from '@/components/Public/PublicHero.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import PublicSupportRequestForm from '@/components/Support/PublicSupportRequestForm.vue';
import { Button } from '@/components/ui/button';
import type { SupportPageContent } from '@/types/publicPages';













type Category = {
    id: number;
    name: string;
    description?: string | null;
};

type SupportFormHandle = {
    selectCategory: (categoryId: number) => void;
};

defineProps<{
    supportPage: SupportPageContent;
    categories: Category[];
    isAuthenticated: boolean;
    mode: 'guest' | 'member';
    initialCategoryId?: number | null;
    attachmentRules: {
        max_kb: number;
        extensions: string[];
    };
}>();

type SupportSuccess = {
    title: string;
    message: string;
    show_tickets_link: boolean;
};

type SharedPageProps = {
    flash?: {
        support_success?: SupportSuccess | null;
    };
};

const page = usePage<SharedPageProps>();

const supportSuccess = computed(
    () => page.props.flash?.support_success ?? null,
);

const requestForm = ref<SupportFormHandle | null>(null);

async function chooseCategory(categoryId: number): Promise<void> {
    requestForm.value?.selectCategory(categoryId);
    await nextTick();

    document
        .getElementById('submit-request')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<template>
    <Head>
        <title>{{ supportPage.seo_title || supportPage.title }}</title>
        <meta
            v-if="supportPage.seo_description || supportPage.introduction"
            name="description"
            :content="supportPage.seo_description || supportPage.introduction || ''"
        />
        <link
            v-if="supportPage.canonical_url"
            rel="canonical"
            :href="supportPage.canonical_url"
        />
    </Head>

    <PublicPageLayout>
        <PublicHero
            :eyebrow="supportPage.eyebrow"
            :title="supportPage.title"
            :description="supportPage.introduction"
            compact
        >
            <template #breadcrumbs>
                <PublicBreadcrumbs
                    :items="[
                        { label: 'Home', href: '/' },
                        { label: supportPage.title },
                    ]"
                />
            </template>

            <template #actions>

                <Button
                    v-if="isAuthenticated"
                    as-child
                    variant="outline"
                >
                    <Link href="/support/tickets">
                        <TicketCheck
                            class="mr-2 h-4 w-4"
                            aria-hidden="true"
                        />
                        View my tickets
                    </Link>
                </Button>

                <Button v-else as-child variant="outline">
                    <Link href="/login">
                        <LogIn
                            class="mr-2 h-4 w-4"
                            aria-hidden="true"
                        />
                        Sign in to track requests
                    </Link>
                </Button>
            </template>
        </PublicHero>

        <div
            v-if="supportPage.header_image_url"
            class="mx-auto mt-8 max-w-[1200px] px-5 sm:px-8 lg:px-12"
        >
            <img
                :src="supportPage.header_image_url"
                :alt="supportPage.header_image_alt || ''"
                class="aspect-[16/7] w-full rounded-3xl object-cover shadow-lg"
            />
        </div>

        <div
            v-if="supportSuccess"
            class="mx-auto mt-8 max-w-[1200px] px-5 sm:px-8 lg:px-12"
        >
            <div
                class="flex flex-col gap-5 rounded-3xl border border-emerald-200 bg-emerald-50 p-6 text-emerald-950 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100"
                role="status"
                aria-live="polite"
            >
                <div class="flex gap-4">
                    <CheckCircle2
                        class="mt-0.5 h-6 w-6 shrink-0"
                        aria-hidden="true"
                    />
                    <div>
                        <h2 class="text-lg font-semibold">
                            {{ supportSuccess.title }}
                        </h2>
                        <p class="mt-1 text-sm leading-6">
                            {{ supportSuccess.message }}
                        </p>
                    </div>
                </div>

                <Button
                    v-if="supportSuccess.show_tickets_link"
                    as-child
                    variant="outline"
                    class="shrink-0 border-emerald-300 bg-white/70 hover:bg-white dark:border-emerald-800 dark:bg-emerald-950"
                >
                    <Link href="/support/tickets">
                        <TicketCheck
                            class="mr-2 h-4 w-4"
                            aria-hidden="true"
                        />
                        View my tickets
                    </Link>
                </Button>
            </div>
        </div>

        <section
            class="mx-auto max-w-[1200px] px-5 py-14 sm:px-8 lg:px-12 lg:py-20"
        >
            <article
                v-if="supportPage.content"
                class="blog-content public-rich-content prose prose-stone max-w-none dark:prose-invert"
                v-html="supportPage.content"
            />

            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <button
                    v-for="category in categories"
                    :key="category.id"
                    type="button"
                    class="group rounded-3xl border border-stone-200 bg-white p-6 text-left shadow-sm transition hover:-translate-y-1 hover:shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 dark:border-stone-800 dark:bg-stone-900"
                    @click="chooseCategory(category.id)"
                >
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-[color-mix(in_srgb,var(--brand-accent)_14%,transparent)] text-[var(--brand-accent)]"
                    >
                        <LifeBuoy
                            class="h-5 w-5"
                            aria-hidden="true"
                        />
                    </div>
                    <h2
                        class="mt-5 text-lg font-semibold transition group-hover:text-[var(--brand-accent)]"
                    >
                        {{ category.name }}
                    </h2>
                    <p
                        class="mt-2 text-sm leading-7 text-stone-600 dark:text-stone-400"
                    >
                        {{ category.description || 'Submit a request for this support topic.' }}
                    </p>
                    <span
                        class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-[var(--brand-accent)]"
                    >
                        Start request
                        <ArrowRight
                            class="h-4 w-4"
                            aria-hidden="true"
                        />
                    </span>
                </button>
            </div>
        </section>

        <section
            id="submit-request"
            class="scroll-mt-24 border-y border-stone-200 bg-stone-50 py-16 dark:border-stone-800 dark:bg-stone-950 lg:py-20"
        >
            <div
                class="mx-auto grid max-w-[1200px] gap-10 px-5 sm:px-8 lg:grid-cols-[0.7fr_1.3fr] lg:px-12"
            >
                <aside class="space-y-6">
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]"
                        >
                            Submit a request
                        </p>
                        <h2 class="mt-3 text-3xl font-semibold">
                            Tell us how we can help
                        </h2>
                        <p
                            class="mt-3 text-sm leading-7 text-stone-600 dark:text-stone-400"
                        >
                            Choose the closest category, explain the issue
                            clearly, and attach a screenshot when it would help
                            us understand the problem.
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-stone-200 bg-white p-5 text-sm leading-7 dark:border-stone-800 dark:bg-stone-900"
                    >
                        <p v-if="mode === 'guest'">
                            We will email you a secure link so you can track and
                            reply to this request without creating an account.
                        </p>
                        <p v-else>
                            This request will be saved in your Support Center so
                            you can track replies and status changes.
                        </p>
                    </div>
                </aside>

                <PublicSupportRequestForm
                    ref="requestForm"
                    :mode="mode"
                    :categories="categories"
                    :initial-category-id="initialCategoryId"
                    :attachment-rules="attachmentRules"
                />
            </div>
        </section>

        <section
            v-if="supportPage.faq_items?.length"
            class="bg-white py-16 dark:bg-stone-900"
        >
            <div class="mx-auto max-w-[1000px] px-5 sm:px-8 lg:px-12">
                <p
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]"
                >
                    Common questions
                </p>
                <h2 class="mt-3 text-3xl font-semibold">Support FAQ</h2>
                <div class="mt-8">
                    <PublicFAQ :items="supportPage.faq_items" />
                </div>
            </div>
        </section>
    </PublicPageLayout>
</template>

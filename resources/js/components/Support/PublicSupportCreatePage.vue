<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PublicBreadcrumbs from '@/components/Public/PublicBreadcrumbs.vue';
import PublicFAQ from '@/components/Public/PublicFAQ.vue';
import PublicHero from '@/components/Public/PublicHero.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import PublicSupportRequestForm from '@/components/Support/PublicSupportRequestForm.vue';

import type { SupportPageContent } from '@/types/publicPages';

defineOptions({ layout: PublicPageLayout });

defineProps<{
    mode: 'guest' | 'member';
    supportPage: SupportPageContent;
    categories: Array<{
        id: number;
        name: string;
        description?: string | null;
    }>;
    initialCategoryId?: number | null;
    attachmentRules: {
        max_kb: number;
        extensions: string[];
    };
}>();
</script>

<template>
    <Head>
        <title>Submit a Support Request</title>
        <meta
            name="description"
            content="Submit a support request to the Unclad Collection team."
        />
    </Head>

    <PublicHero
        eyebrow="Support"
        title="Submit a support request"
        description="Tell us what happened and include any details that will help us investigate."
        compact
    >
        <template #breadcrumbs>
            <PublicBreadcrumbs
                :items="[
                    { label: 'Home', href: '/' },
                    { label: supportPage.title, href: '/support' },
                    { label: 'Submit a request' },
                ]"
            />
        </template>
    </PublicHero>

    <section class="mx-auto grid max-w-[1200px] gap-10 px-5 py-14 sm:px-8 lg:grid-cols-[0.7fr_1.3fr] lg:px-12 lg:py-20">
        <aside class="space-y-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">
                    Before you submit
                </p>
                <h2 class="mt-3 text-2xl font-semibold">Help us resolve it faster</h2>
                <p class="mt-3 text-sm leading-7 text-stone-600 dark:text-stone-400">
                    Choose the closest category, explain the issue clearly, and attach a screenshot when it would help us understand the problem.
                </p>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-stone-50 p-5 text-sm leading-7 dark:border-stone-800 dark:bg-stone-900">
                <p v-if="mode === 'guest'">
                    We will email you a secure link so you can track and reply to this request without creating an account.
                </p>
                <p v-else>
                    This request will be saved in your Support Center so you can track replies and status changes.
                </p>
            </div>
        </aside>

        <PublicSupportRequestForm
            :mode="mode"
            :categories="categories"
            :initial-category-id="initialCategoryId"
            :attachment-rules="attachmentRules"
        />
    </section>

    <section v-if="supportPage.faq_items?.length" class="mx-auto max-w-[1000px] px-5 pb-20 sm:px-8 lg:px-12">
        <h2 class="mb-6 text-2xl font-semibold">Support questions</h2>
        <PublicFAQ :items="supportPage.faq_items" />
    </section>
</template>

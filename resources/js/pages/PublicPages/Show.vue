<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import PublicBreadcrumbs from '@/components/Public/PublicBreadcrumbs.vue';
import PublicFAQ from '@/components/Public/PublicFAQ.vue';
import PublicHero from '@/components/Public/PublicHero.vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { PublicPageRecord } from '@/types/publicPages';











const props = defineProps<{
    publicPage: PublicPageRecord;
}>();

const user = (usePage().props.auth as any)?.user;

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
        <PublicHero
            :eyebrow="publicPage.eyebrow"
            :title="publicPage.title"
            :description="publicPage.introduction"
            compact
        >
            <template #breadcrumbs>
                <PublicBreadcrumbs
                    :items="[
                        {
                            label: 'Home',
                            href: '/',
                        },
                        {
                            label: publicPage.title,
                        },
                    ]"
                />
            </template>
        </PublicHero>

        <div
            v-if="publicPage.header_image_url"
            class="mx-auto mt-8 max-w-[1200px] px-5 sm:px-8 lg:px-12"
        >
            <img
                :src="publicPage.header_image_url"
                :alt="publicPage.header_image_alt || ''"
                class="aspect-[16/7] w-full rounded-3xl object-cover shadow-lg"
            />
        </div>

        <section
            class="mx-auto max-w-[1000px] px-5 py-14 sm:px-8 lg:px-12 lg:py-20"
        >
            <div
                v-if="
                    publicPage.page_type === 'legal'
                    && (
                        publicPage.legal_version
                        || publicPage.effective_date
                        || publicPage.revised_date
                    )
                "
                class="mb-8 flex flex-wrap gap-3 text-sm text-stone-500"
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

            <article
                v-if="publicPage.content"
                class="blog-content public-rich-content prose prose-stone max-w-none dark:prose-invert"
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
                class="mt-10 space-y-5 rounded-3xl border bg-white p-6 shadow-sm dark:bg-stone-900 sm:p-8"
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
        </section>
    </PublicPageLayout>
</template>
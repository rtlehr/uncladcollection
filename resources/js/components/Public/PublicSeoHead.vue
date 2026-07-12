<script setup lang="ts">
import {
    Head,
    usePage,
} from '@inertiajs/vue3';
import { computed } from 'vue';

import type { SharedSeoSettings } from '@/types/seo';

const props = withDefaults(defineProps<{
    title?: string | null;
    description?: string | null;
    image?: string | null;
    canonicalPath?: string | null;
    type?: 'website' | 'article';
    robots?: string;
    publishedTime?: string | null;
    modifiedTime?: string | null;
    authorName?: string | null;
}>(), {
    title: null,
    description: null,
    image: null,
    canonicalPath: null,
    type: 'website',
    robots: 'index, follow, max-image-preview:large',
    publishedTime: null,
    modifiedTime: null,
    authorName: null,
});

const page = usePage();

const seo = computed<SharedSeoSettings>(() => {
    const shared = (page.props.seo ?? {}) as Partial<SharedSeoSettings>;

    return {
        site_url: String(
            shared.site_url
            ?? window.location.origin,
        ).replace(/\/+$/, ''),

        site_name: String(
            shared.site_name
            ?? 'Unclad Collection',
        ),

        default_title: String(
            shared.default_title
            ?? 'Unclad Collection',
        ),

        default_description: String(
            shared.default_description
            ?? 'Licensed imagery and thoughtful stories for the nudist community.',
        ),

        default_image_url:
            shared.default_image_url
            ?? null,

        x_username:
            shared.x_username
            ?? null,

        locale: String(
            shared.locale
            ?? 'en_US',
        ),
    };
});

const pageTitle = computed(() => {
    const title = props.title?.trim();

    if (!title) {
        return seo.value.default_title;
    }

    if (
        title
            .toLowerCase()
            .includes(
                seo.value.site_name.toLowerCase(),
            )
    ) {
        return title;
    }

    return `${title} | ${seo.value.site_name}`;
});

const description = computed(() =>
    props.description?.trim()
    || seo.value.default_description,
);

function absoluteUrl(
    value: string | null | undefined,
): string | null {
    if (!value) {
        return null;
    }

    if (/^https?:\/\//i.test(value)) {
        return value;
    }

    return `${seo.value.site_url}/${value.replace(/^\/+/, '')}`;
}

const canonicalUrl = computed(() => {
    const path =
        props.canonicalPath
        ?? window.location.pathname;

    return absoluteUrl(path)
        ?? seo.value.site_url;
});

const imageUrl = computed(() =>
    absoluteUrl(props.image)
    ?? absoluteUrl(seo.value.default_image_url),
);
</script>

<template>
    <Head>
        <title>{{ pageTitle }}</title>

        <meta
            name="description"
            :content="description"
        />

        <meta
            name="robots"
            :content="robots"
        />

        <link
            rel="canonical"
            :href="canonicalUrl"
        />

        <meta
            property="og:locale"
            :content="seo.locale"
        />

        <meta
            property="og:type"
            :content="type"
        />

        <meta
            property="og:site_name"
            :content="seo.site_name"
        />

        <meta
            property="og:title"
            :content="pageTitle"
        />

        <meta
            property="og:description"
            :content="description"
        />

        <meta
            property="og:url"
            :content="canonicalUrl"
        />

        <meta
            v-if="imageUrl"
            property="og:image"
            :content="imageUrl"
        />

        <meta
            v-if="imageUrl"
            property="og:image:alt"
            :content="title || seo.site_name"
        />

        <meta
            v-if="type === 'article' && publishedTime"
            property="article:published_time"
            :content="publishedTime"
        />

        <meta
            v-if="type === 'article' && modifiedTime"
            property="article:modified_time"
            :content="modifiedTime"
        />

        <meta
            v-if="type === 'article' && authorName"
            property="article:author"
            :content="authorName"
        />

        <meta
            name="twitter:card"
            :content="
                imageUrl
                    ? 'summary_large_image'
                    : 'summary'
            "
        />

        <meta
            name="twitter:title"
            :content="pageTitle"
        />

        <meta
            name="twitter:description"
            :content="description"
        />

        <meta
            v-if="imageUrl"
            name="twitter:image"
            :content="imageUrl"
        />

        <meta
            v-if="seo.x_username"
            name="twitter:site"
            :content="seo.x_username"
        />
    </Head>
</template>

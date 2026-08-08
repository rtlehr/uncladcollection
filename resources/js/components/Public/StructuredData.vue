<script setup lang="ts">
import {
    Head,
    usePage,
} from '@inertiajs/vue3';
import { computed } from 'vue';

import type {
    SeoBreadcrumb,
    SeoPrimarySchema,
    SharedSeoSettings,
} from '@/types/seo';

const props = withDefaults(defineProps<{
    breadcrumbs?: SeoBreadcrumb[];
    primarySchema?: SeoPrimarySchema | null;
    image?: string | null;
}>(), {
    breadcrumbs: () => [],
    primarySchema: null,
    image: null,
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

function absolutizeSchemaValue(value: unknown): unknown {
    if (Array.isArray(value)) {
        return value.map(absolutizeSchemaValue);
    }

    if (
        value
        && typeof value === 'object'
    ) {
        return Object.fromEntries(
            Object.entries(
                value as Record<string, unknown>,
            ).map(([key, item]) => {
                if (
                    [
                        'url',
                        'contentUrl',
                        'thumbnailUrl',
                        'mainEntityOfPage',
                        'primaryImageOfPage',
                        'item',
                    ].includes(key)
                    && typeof item === 'string'
                ) {
                    return [
                        key,
                        absoluteUrl(item),
                    ];
                }

                return [
                    key,
                    absolutizeSchemaValue(item),
                ];
            }),
        );
    }

    return value;
}

const schemas = computed<SeoPrimarySchema[]>(() => {
    const organizationId =
        `${seo.value.site_url}/#organization`;

    const websiteId =
        `${seo.value.site_url}/#website`;

    const imageUrl =
        absoluteUrl(props.image)
        ?? absoluteUrl(seo.value.default_image_url);

    const values: SeoPrimarySchema[] = [
        {
            '@context': 'https://schema.org',
            '@type': 'Organization',
            '@id': organizationId,
            name: seo.value.site_name,
            url: seo.value.site_url,

            ...(imageUrl
                ? {
                    logo: {
                        '@type': 'ImageObject',
                        url: imageUrl,
                    },
                }
                : {}),
        },

        {
            '@context': 'https://schema.org',
            '@type': 'WebSite',
            '@id': websiteId,
            name: seo.value.site_name,
            url: seo.value.site_url,

            publisher: {
                '@id': organizationId,
            },

            potentialAction: {
                '@type': 'SearchAction',

                target: {
                    '@type': 'EntryPoint',
                    urlTemplate:
                        `${seo.value.site_url}/images?search={search_term_string}`,
                },

                'query-input':
                    'required name=search_term_string',
            },
        },
    ];

    if (props.breadcrumbs.length) {
        values.push({
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',

            itemListElement:
                props.breadcrumbs.map(
                    (item, index) => ({
                        '@type': 'ListItem',
                        position: index + 1,
                        name: item.name,
                        item: absoluteUrl(item.url),
                    }),
                ),
        });
    }

    if (props.primarySchema) {
        values.push({
            '@context': 'https://schema.org',

            ...(
                absolutizeSchemaValue(
                    props.primarySchema,
                ) as SeoPrimarySchema
            ),
        });
    }

    return values;
});
</script>

<template>
    <Head>
        <component
            :is="'script'"
            v-for="(schema, index) in schemas"
            :key="`structured-data-${index}`"
            type="application/ld+json"
            :textContent="JSON.stringify(schema)"
        />
    </Head>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ImageIcon, LayoutGrid, Library, ShoppingBag } from '@lucide/vue';
import { ref } from 'vue';

import AssetCard from '@/Components/Assets/AssetCard.vue';
import AssetDescription from '@/Components/Assets/AssetDescription.vue';
import AssetHero from '@/Components/Assets/AssetHero.vue';
import AssetMetadata from '@/Components/Assets/AssetMetadata.vue';
import AssetPreview from '@/Components/Assets/AssetPreview.vue';
import AssetStats from '@/Components/Assets/AssetStats.vue';
import DownloadButton from '@/Components/Assets/DownloadButton.vue';
import LicenseSelector from '@/Components/Assets/LicenseSelector.vue';
import BlogAuthorCard from '@/Components/Blog/BlogAuthorCard.vue';
import BlogMeta from '@/Components/Blog/BlogMeta.vue';
import BlogPostCard from '@/Components/Blog/BlogPostCard.vue';
import BlogPostList from '@/Components/Blog/BlogPostList.vue';
import PurchaseSummary from '@/Components/Purchases/PurchaseSummary.vue';
import PurchasedAssetCard from '@/Components/Purchases/PurchasedAssetCard.vue';
import ChipList from '@/Components/Shared/ChipList.vue';
import DetailRow from '@/Components/Shared/DetailRow.vue';
import DetailSection from '@/Components/Shared/DetailSection.vue';
import EmptyState from '@/Components/Shared/EmptyState.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import SectionHeader from '@/Components/Shared/SectionHeader.vue';
import SidebarCard from '@/Components/Shared/SidebarCard.vue';
import StatCard from '@/Components/Shared/StatCard.vue';
import UserAvatar from '@/Components/Shared/UserAvatar.vue';
import { Button } from '@/components/ui/button';

import type {
    AssetCardData,
    AssetDetailData,
    LicenseType,
} from '@/types/asset';
import type { BlogPost } from '@/types/blog';
import type {
    PurchaseDetailRecord,
    PurchasedAsset,
} from '@/types/purchase';

const categories = [
    { id: 1, name: 'Lifestyle' },
    { id: 2, name: 'Beach' },
    { id: 3, name: 'Community' },
];

const tags = [
    { id: 1, name: 'naturism' },
    { id: 2, name: 'photography' },
    { id: 3, name: 'summer' },
];

const sampleAsset: AssetCardData = {
    id: 101,
    title: 'Coastal Morning',
    slug: 'coastal-morning',
    photographer: 'Sample Photographer',
    thumbnail_url: null,
    icon_url: null,
    is_ai_generated: false,
    favorites_count: 245,
    downloads_count: 81,
    purchases_count: 34,
    views_count: 1824,
    collection: { id: 1, name: 'Coastal Living' },
    categories,
    tags,
    asset_type: 'raster',
    file_extension: 'jpg',
    mime_type: 'image/jpeg',
};

const sampleAssetDetail: AssetDetailData = {
    ...sampleAsset,
    description:
        'A sample asset description used to demonstrate the reusable asset detail components.',
    original_url: null,
    high_res_url: null,
    is_favorited: true,
    is_purchased: true,
    can_purchase: false,
    can_download: false,
    created_at: 'July 10, 2026',
};

const sampleAuthor = {
    id: 1,
    name: 'Sample Author',
    author_title: 'Community Writer',
    author_bio:
        'A sample contributor profile used to demonstrate the reusable blog author component.',
    author_website_url: null,
    avatar_url: null,
};

const sampleBlogPost: BlogPost = {
    id: 201,
    title: 'A Sample Article for the UI Kit',
    slug: 'sample-article-ui-kit',
    excerpt:
        'This is sample content used to demonstrate the reusable blog card and metadata components.',
    content: '<p>Sample content.</p>',
    featured_image_url: null,
    header_image_url: null,
    icon_image_url: null,
    published_at: '2026-07-10',
    views_count: 482,
    seo_title: null,
    seo_description: null,
    author: sampleAuthor,
    categories: [categories[0], categories[2]],
    tags,
    user_id: 1,
    comments_enabled: true,
    comments_visible: true,
    is_featured: true,
};

const samplePurchasedAsset: PurchasedAsset = {
    id: 301,
    license_key: 'UC-SAMPLE-12345',
    license_name: 'Standard License',
    downloads_used: 2,
    download_limit: 10,
    starts_at: 'July 1, 2026',
    expires_at: null,
    image: {
        id: 101,
        title: 'Coastal Morning',
        slug: 'coastal-morning',
        photographer: 'Sample Photographer',
        thumbnail_url: null,
        icon_url: null,
        is_ai_generated: false,
        favorites_count: 245,
        downloads_count: 81,
        purchases_count: 34,
        views_count: 1824,
    },
    order: {
        id: 401,
        order_number: 'UC-2026-0001',
        paid_at: 'July 1, 2026',
        total_formatted: '$24.00',
    },
};

const samplePurchaseDetail: PurchaseDetailRecord = {
    id: 301,
    license_key: 'UC-SAMPLE-12345',
    license_name: 'Standard License',
    license_terms:
        'Sample license terms used only for the component showcase.',
    downloads_used: 2,
    download_limit: 10,
    starts_at: 'July 1, 2026',
    expires_at: null,
    can_download: false,
    image: {
        id: 101,
        title: 'Coastal Morning',
        slug: 'coastal-morning',
        description: 'Sample purchased asset description.',
        photographer: 'Sample Photographer',
        thumbnail_url: null,
        high_res_url: null,
        original_url: null,
        is_ai_generated: false,
        created_at: 'June 20, 2026',
        collection: { id: 1, name: 'Coastal Living' },
        categories,
        tags,
    },
    order: {
        id: 401,
        order_number: 'UC-2026-0001',
        paid_at: 'July 1, 2026',
        total_formatted: '$24.00',
    },
};

const licenseTypes: LicenseType[] = [
    {
        id: 1,
        name: 'Standard License',
        description: 'Sample standard license.',
        price_cents: 2400,
        currency: 'USD',
    },
    {
        id: 2,
        name: 'Extended License',
        description: 'Sample extended license.',
        price_cents: 5900,
        currency: 'USD',
    },
];

const selectedLicenseTypeId = ref<number | null>(licenseTypes[0].id);
</script>

<template>
    <Head title="UI Kit" />

    <div class="space-y-12 p-6">
        <PageHeader
            eyebrow="Development"
            title="Unclad Collection UI Kit"
            description="A living showcase of reusable components used throughout the application."
        >
            <div class="mt-6 flex flex-wrap gap-2">
                <Button as-child variant="outline" size="sm">
                    <a href="#shared">Shared</a>
                </Button>

                <Button as-child variant="outline" size="sm">
                    <a href="#assets">Assets</a>
                </Button>

                <Button as-child variant="outline" size="sm">
                    <a href="#blog">Blog</a>
                </Button>

                <Button as-child variant="outline" size="sm">
                    <a href="#purchases">Purchases</a>
                </Button>
            </div>
        </PageHeader>

        <section id="shared" class="scroll-mt-6 space-y-8">
            <SectionHeader
                eyebrow="Foundation"
                title="Shared Components"
                description="Generic building blocks used across multiple application modules."
            />

            <div class="grid gap-6 lg:grid-cols-2">
                <DetailSection title="Headers">
                    <div class="space-y-8">
                        <PageHeader
                            eyebrow="Eyebrow"
                            title="Sample Page Header"
                            description="Page-level title and supporting copy."
                        />

                        <SectionHeader
                            eyebrow="Section"
                            title="Sample Section Header"
                            description="Section-level heading with optional actions."
                        >
                            <template #actions>
                                <Button variant="outline" size="sm">
                                    Action
                                </Button>
                            </template>
                        </SectionHeader>
                    </div>
                </DetailSection>

                <DetailSection title="Identity and Lists">
                    <div class="space-y-6">
                        <div class="flex items-center gap-3">
                            <UserAvatar
                                :src="null"
                                alt="Sample user"
                                size="md"
                            />

                            <div>
                                <div class="font-medium">User Avatar</div>
                                <div class="text-sm text-muted-foreground">
                                    The avatar is hidden when no source is available.
                                </div>
                            </div>
                        </div>

                        <ChipList :items="categories" />

                        <ChipList
                            :items="tags"
                            prefix="#"
                            size="sm"
                        />
                    </div>
                </DetailSection>
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Views" value="1,824" />
                <StatCard label="Favorites" value="245" />
                <StatCard label="Downloads" value="81" />
                <StatCard label="Purchases" value="34" />
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <SidebarCard title="Sidebar Card">
                    <p class="text-sm leading-6 text-muted-foreground">
                        Generic sidebar content belongs here.
                    </p>
                </SidebarCard>

                <DetailSection title="Detail Rows">
                    <div class="space-y-4">
                        <DetailRow label="Order Number" value="UC-2026-0001" />
                        <DetailRow label="Expires" value="Never" />
                        <DetailRow
                            label="License Key"
                            value="UC-SAMPLE-12345"
                            break-all
                        />
                    </div>
                </DetailSection>
            </div>

            <EmptyState
                title="Sample empty state"
                description="Use this component whenever a list or result set contains no records."
            >
                <template #icon>
                    <LayoutGrid class="h-5 w-5" />
                </template>

                <template #actions>
                    <Button variant="outline">
                        Sample Action
                    </Button>
                </template>
            </EmptyState>
        </section>

        <section id="assets" class="scroll-mt-6 space-y-8">
            <SectionHeader
                eyebrow="Marketplace"
                title="Asset Components"
                description="Reusable display components for raster, vector, and future archive assets."
            />

            <AssetHero
                title="Sample Asset Hero"
                collection-name="Coastal Living"
            >
                <template #actions>
                    <DownloadButton
                        :asset-id="101"
                        :can-download="false"
                        :is-purchased="true"
                    />
                </template>
            </AssetHero>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <AssetPreview
                        :src="null"
                        alt="Sample asset preview"
                        aspect="landscape"
                        fallback-text="Asset preview fallback"
                    />
                </div>

                <div class="space-y-6">
                    <AssetMetadata :asset="sampleAssetDetail" />
                    <AssetStats :asset="sampleAssetDetail" />
                </div>
            </div>

            <AssetDescription
                :description="sampleAssetDetail.description"
            />

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <AssetCard :asset="sampleAsset" />
                <AssetCard
                    :asset="sampleAsset"
                    :show-categories="false"
                />
            </div>

            <DetailSection title="License Selector">
                <div class="max-w-sm">
                    <LicenseSelector
                        v-model="selectedLicenseTypeId"
                        :license-types="licenseTypes"
                    />

                    <p class="mt-2 text-sm text-muted-foreground">
                        Selected license ID: {{ selectedLicenseTypeId }}
                    </p>
                </div>
            </DetailSection>
        </section>

        <section id="blog" class="scroll-mt-6 space-y-8">
            <SectionHeader
                eyebrow="Content"
                title="Blog Components"
                description="Reusable cards, metadata, authors, and post lists."
            />

            <BlogMeta
                author-name="Sample Author"
                :author-avatar="null"
                avatar-alt="Sample Author"
                published-date="July 10, 2026"
                reading-time="4 min read"
                :views-count="482"
            />

            <div class="grid gap-6 lg:grid-cols-3">
                <BlogPostCard
                    :post="sampleBlogPost"
                    class="lg:col-span-2"
                />

                <div class="space-y-6">
                    <BlogAuthorCard :author="sampleAuthor" />

                    <BlogPostList
                        title="More Sample Content"
                        :posts="[sampleBlogPost]"
                    />
                </div>
            </div>
        </section>

        <section id="purchases" class="scroll-mt-6 space-y-8">
            <SectionHeader
                eyebrow="Commerce"
                title="Purchase Components"
                description="Reusable components for licenses, purchase history, and download details."
            />

            <div class="grid gap-6 lg:grid-cols-3">
                <PurchasedAssetCard :license="samplePurchasedAsset" />

                <div class="lg:col-span-2">
                    <PurchaseSummary :license="samplePurchaseDetail" />
                </div>
            </div>
        </section>

        <div class="border-t pt-6 text-sm text-muted-foreground">
            <Link href="/" class="hover:text-foreground hover:underline">
                Return to the application
            </Link>
        </div>
    </div>
</template>

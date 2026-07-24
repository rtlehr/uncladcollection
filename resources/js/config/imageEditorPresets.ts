import type { ImageEditorPreset } from '@/components/media/ImageEditorDialog.vue';

export const IMAGE_EDITOR_PRESETS: Record<string, ImageEditorPreset> = {
    marketplaceCard: {
        key: 'marketplace-card',
        label: 'Marketplace card',
        width: 1200,
        height: 675,
        outputType: 'image/jpeg',
        quality: 0.9,
    },
    marketingHero: {
        key: 'marketing-hero',
        label: 'Marketing hero',
        width: 1920,
        height: 800,
        outputType: 'image/jpeg',
        quality: 0.9,
    },
    homepageHero: {
        key: 'homepage-hero',
        label: 'Homepage hero',
        width: 1600,
        height: 900,
        outputType: 'image/jpeg',
        quality: 0.9,
    },
    blogIcon: {
        key: 'blog-icon',
        label: 'Blog icon',
        width: 600,
        height: 600,
        outputType: 'image/jpeg',
        quality: 0.9,
    },
    blogContentLandscape: {
        key: 'blog-content-landscape',
        label: 'Article image — Landscape',
        width: 1200,
        height: 800,
        outputType: 'image/jpeg',
        quality: 0.9,
    },
    blogContentSquare: {
        key: 'blog-content-square',
        label: 'Article image — Square',
        width: 300,
        height: 300,
        outputType: 'image/jpeg',
        quality: 0.9,
    },
    blogContentPortrait: {
        key: 'blog-content-portrait',
        label: 'Article image — Portrait',
        width: 800,
        height: 1200,
        outputType: 'image/jpeg',
        quality: 0.9,
    },
    blogHeader: {
        key: 'blog-header',
        label: 'Blog header',
        width: 1800,
        height: 688,
        outputType: 'image/jpeg',
        quality: 0.9,
    },
    collectionCover: {
        key: 'collection-cover',
        label: 'Collection cover',
        width: 1200,
        height: 750,
        outputType: 'image/jpeg',
        quality: 0.9,
    },
    socialShare: {
        key: 'social-share',
        label: 'Social share',
        width: 1200,
        height: 630,
        outputType: 'image/jpeg',
        quality: 0.9,
    },
    square: {
        key: 'square',
        label: 'Square',
        width: 800,
        height: 800,
        outputType: 'image/jpeg',
        quality: 0.9,
    },
    avatar: {
        key: 'avatar',
        label: 'Avatar',
        width: 600,
        height: 600,
        outputType: 'image/jpeg',
        quality: 0.9,
    },
};

export const MARKETING_HERO_PRESET =
    IMAGE_EDITOR_PRESETS.marketingHero;

export const MARKETPLACE_CARD_PRESET =
    IMAGE_EDITOR_PRESETS.marketplaceCard;

export const BLOG_HEADER_PRESET = IMAGE_EDITOR_PRESETS.blogHeader;
export const BLOG_ICON_PRESET = IMAGE_EDITOR_PRESETS.blogIcon;
export const BLOG_CONTENT_PRESETS = [
    IMAGE_EDITOR_PRESETS.blogContentLandscape,
    IMAGE_EDITOR_PRESETS.blogContentPortrait,
    IMAGE_EDITOR_PRESETS.blogContentSquare,
];

export const COLLECTION_COVER_PRESET =
    IMAGE_EDITOR_PRESETS.collectionCover;

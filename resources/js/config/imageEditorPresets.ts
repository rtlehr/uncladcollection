import type { ImageEditorPreset } from '@/components/media/ImageEditorDialog.vue';

export const IMAGE_EDITOR_PRESETS: Record<string, ImageEditorPreset> = {
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
        height: 800,
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

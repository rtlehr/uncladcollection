export type PublicSiteSettings = Record<
    string,
    Record<string, string | number | boolean | string[] | null>
>;

export interface HomeHeroImage {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    photographer: string | null;
    image_url: string | null;
}

export interface HomeCollection {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    images_count: number;
    cover_image: {
        title: string;
        slug: string;
        thumbnail_url: string | null;
        icon_url: string | null;
    } | null;
}

export interface HomeImage {
    id: number;
    title: string;
    slug: string;
    photographer: string | null;
    thumbnail_url: string | null;
    icon_url: string | null;
    is_ai_generated: boolean;
    favorites_count: number;
    collection: {
        id: number;
        name: string;
        slug: string;
    } | null;
}

export interface HomeArticle {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    featured_image_url: string | null;
    header_image_url: string | null;
    icon_image_url: string | null;
    published_at: string | null;
    is_featured: boolean;
    author?: {
        id: number;
        name: string;
    } | null;
    categories?: Array<{
        id: number;
        name: string;
        slug: string;
    }>;
}

export interface HomeStatistics {
    images: number;
    collections: number;
    articles: number;
    downloads: number;
}


export interface HomeHeroCampaign {
    id: number;
    name: string;
    media_type: 'image' | 'video';
    media_url: string;
    poster_url: string | null;
    eyebrow: string | null;
    headline: string | null;
    subheadline: string | null;
    primary_button_label: string | null;
    primary_button_url: string | null;
    secondary_button_label: string | null;
    secondary_button_url: string | null;
    overlay_opacity: number;
    media_position: 'center' | 'top' | 'bottom' | 'left' | 'right';
    hero_height: 'compact' | 'medium' | 'large' | 'fullscreen';
    text_alignment: 'left' | 'center' | 'right';
    autoplay_first_visit: boolean;
    autoplay_mobile: boolean;
    loop_video: boolean;
    show_search: boolean;
}

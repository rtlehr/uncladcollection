import type {
    GalleryImage,
    PaginatedGalleryImages,
} from '@/types/gallery';
import type { BlogPost } from '@/types/blog';

export interface PublicCollection {
    id: number;
    name: string;
    slug: string;
    description: string | null;
}

export interface CollectionHeroImage {
    id: number;
    title: string;
    slug: string;
    image_url: string | null;
}

export interface CollectionStatistics {
    images: number;
    views: number;
    favorites: number;
    downloads: number;
}

export interface RelatedCollection {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    images_count: number;
    cover_image_url: string | null;
}

export interface CollectionFilters {
    search: string;
    sort: string;
}

export type CollectionImages = PaginatedGalleryImages;
export type CollectionArticle = BlogPost;
export type CollectionGalleryImage = GalleryImage;

import type { ImageEditData } from '@/components/media/ImageEditorDialog.vue';
import type { BlogPost } from '@/types/blog';
import type {
    GalleryImage,
    PaginatedGalleryImages,
} from '@/types/gallery';

export interface AdminCollection {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
    is_active: boolean;
    cover_image_url: string | null;
    cover_original_url: string | null;
    cover_edit_data: ImageEditData | null;
    created_at?: string;
    updated_at?: string;
}

export interface AdminCollectionFilters {
    search: string | null;
    status: string | null;
    sort: string;
    direction: 'asc' | 'desc';
}

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

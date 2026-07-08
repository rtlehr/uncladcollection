import type { BlogPost } from '@/types/blog';

export function contentImage(post: BlogPost): string | null {
    return (
        post.header_image_url ??
        post.featured_image_url ??
        post.icon_image_url ??
        null
    );
}
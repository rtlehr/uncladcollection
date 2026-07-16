import type { BlogPost } from '@/types/blog';

export function articleHeaderImage(post: BlogPost): string | null {
    return (
        post.header_image_url
        ?? post.featured_image_url
        ?? post.icon_image_url
        ?? null
    );
}

export function blogCardImage(post: BlogPost): string | null {
    return (
        post.icon_image_url
        ?? post.header_image_url
        ?? post.featured_image_url
        ?? null
    );
}

// Backward-compatible default for older components that still expect the
// article/header image behavior.
export function contentImage(post: BlogPost): string | null {
    return articleHeaderImage(post);
}

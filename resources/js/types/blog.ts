export interface Author {
    id: number;
    name: string;

    author_title?: string | null;
    author_bio?: string | null;
    author_website_url?: string | null;

    avatar_url?: string | null;
}

export interface Category {
    id: number;
    name: string;
    slug?: string;
}

export interface Tag {
    id: number;
    name: string;
    slug?: string;
}

export interface BlogPost {
    id: number;
    title: string;
    slug: string;

    excerpt: string | null;
    content: string | null;

    featured_image_url: string | null;
    header_image_url: string | null;
    icon_image_url: string | null;

    published_at: string | null;
    views_count: number;

    seo_title?: string | null;
    seo_description?: string | null;

    author: Author | null;

    categories: Category[];
    tags: Tag[];

    user_id: number;

    comments_enabled: boolean;
    comments_visible: boolean;
}
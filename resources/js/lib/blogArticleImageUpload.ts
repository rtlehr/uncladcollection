import type { ImageEditData } from '@/components/media/ImageEditorDialog.vue';

export type BlogArticleImageUploadSource = {
    assetId?: number | null;
    assetSlug?: string | null;
    photographer?: string | null;
    publicUrl?: string | null;
    title?: string | null;
};

export type UploadedBlogArticleImage = {
    url: string;
    path: string;
    preset: string;
    alt: string;
    assetId: number | null;
    assetSlug: string | null;
    photographer: string | null;
    publicUrl: string | null;
    title: string | null;
};

export async function uploadBlogArticleImage(
    file: File,
    edit: ImageEditData,
    alt: string,
    source: BlogArticleImageUploadSource = {},
): Promise<UploadedBlogArticleImage> {
    const formData = new FormData();

    formData.append('image', file);
    formData.append('preset', edit.preset);
    formData.append('edit_data', JSON.stringify(edit));
    formData.append('alt', alt);

    if (source.assetId) {
        formData.append('asset_id', String(source.assetId));
    }

    const csrfToken =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? '';

    const response = await fetch(
        '/admin/blog-posts/upload-content-image',
        {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
        },
    );

    if (!response.ok) {
        const data = await response.json().catch(() => null);

        throw new Error(
            data?.message ?? 'Article image upload failed.',
        );
    }

    const data = await response.json();

    return {
        url: data.url,
        path: data.path,
        preset: data.preset,
        alt: data.alt ?? alt,
        assetId: data.asset?.id ?? source.assetId ?? null,
        assetSlug: data.asset?.slug ?? source.assetSlug ?? null,
        photographer:
            data.asset?.photographer ?? source.photographer ?? null,
        publicUrl:
            data.asset?.public_url ?? source.publicUrl ?? null,
        title: data.asset?.title ?? source.title ?? null,
    };
}

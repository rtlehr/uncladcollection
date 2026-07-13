export type MediaPreviewKind = 'image' | 'video' | 'document' | 'unavailable';

export interface MediaPresentationFile {
    id: number;
    role: string;
    role_label?: string;
    media_type: string;
    original_filename: string;
    extension: string;
    mime_type: string | null;
    size_bytes: number | null;
    width: number | null;
    height: number | null;
    duration_seconds: number | string | null;
    page_count?: number | null;
    is_downloadable: boolean;
    can_preview: boolean;
    preview_kind: MediaPreviewKind;
    preview_url: string | null;
    poster_url: string | null;
    preview_note: string | null;
}

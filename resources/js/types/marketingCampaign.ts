import type { ImageEditData } from '@/components/media/ImageEditorDialog.vue';

export interface MarketingCampaign {
    id: number;
    uuid: string;
    name: string;
    media_type: 'image' | 'video';
    media_url: string | null;
    media_original_url: string | null;
    media_edit_data: ImageEditData | null;
    poster_url: string | null;
    eyebrow: string | null;
    headline: string | null;
    subheadline: string | null;
    primary_button_label: string | null;
    primary_button_url: string | null;
    secondary_button_label: string | null;
    secondary_button_url: string | null;
    overlay_opacity: number;
    media_position: string;
    hero_height: string;
    text_alignment: string;
    autoplay_first_visit: boolean;
    autoplay_mobile: boolean;
    loop_video: boolean;
    show_search: boolean;
    is_active: boolean;
    is_current: boolean;
    sort_order: number;
    starts_at: string | null;
    ends_at: string | null;
}

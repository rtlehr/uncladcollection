export type SiteSettingType =
    | 'text'
    | 'email'
    | 'url'
    | 'color'
    | 'boolean'
    | 'textarea'
    | 'image'
    | string;

export interface SiteSetting {
    id: number;
    group_name: string;
    setting_key: string;
    setting_value: string | null;
    setting_type: SiteSettingType;
    description: string | null;
    is_public: boolean;
}

export interface SiteSettingFormValue {
    id: number;
    setting_value: string;
}

export type GroupedSiteSettings = Record<string, SiteSetting[]>;

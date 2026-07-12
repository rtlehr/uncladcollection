export interface SeoBreadcrumb {
    name: string;
    url: string;
}

export interface SeoPrimarySchema {
    '@type': string;
    [key: string]: unknown;
}

export interface SharedSeoSettings {
    site_url: string;
    site_name: string;
    default_title: string;
    default_description: string;
    default_image_url: string | null;
    x_username: string | null;
    locale: string;
}

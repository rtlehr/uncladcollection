import type { ConfigurationDisplayTypeOption } from '@/types/adminAsset';

export interface ConfigurationTemplateValue {
    id: number | null;
    label: string;
    value: string;
    description: string | null;
    swatch_color: string | null;
    image_path: string | null;
    price_adjustment_cents: number;
    currency: string;
    is_default: boolean;
    is_active: boolean;
}

export interface ConfigurationTemplate {
    id: number;
    name: string;
    code: string;
    description: string | null;
    display_type: string;
    display_type_label: string;
    is_required_default: boolean;
    allows_multiple_default: boolean;
    placeholder: string | null;
    help_text: string | null;
    minimum_value: string | number | null;
    maximum_value: string | number | null;
    step_value: string | number | null;
    sort_order: number;
    is_active: boolean;
    values_count: number;
    values?: ConfigurationTemplateValue[];
}

export interface ConfigurationTemplateSummary extends ConfigurationTemplate {
    asset_group: import('@/types/adminAsset').AdminAssetConfigurationGroup;
}

export type { ConfigurationDisplayTypeOption };

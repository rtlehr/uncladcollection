export type AnalyticsMetric = {
    value: number;
    previous_value: number;
    change_percent: number | null;
};

export type AnalyticsMetrics = Record<string, AnalyticsMetric>;

export type RevenueTrendPoint = {
    date: string;
    label: string;
    revenue_cents: number;
    orders_count: number;
};

export type FunnelStage = {
    key: string;
    label: string;
    value: number;
    conversion_percent: number;
};

export type DistributionItem = {
    label: string;
    revenue_cents: number;
    units: number;
};

export type TopAsset = {
    asset_id: number;
    title: string;
    slug: string | null;
    units: number;
    revenue_cents: number;
};

export type MarketplaceHealth = {
    published_assets: number;
    active_offerings: number;
    active_licenses: number;
    failed_order_rate_percent: number;
    refund_rate_percent: number;
    downloads_per_paid_order: number;
};

export type RecentMarketplaceActivity = {
    type: 'order' | 'download';
    title: string;
    description: string;
    amount_cents: number | null;
    occurred_at: string | null;
    href: string;
};

export type ExecutiveDashboard = {
    summary: AnalyticsMetrics;
    revenue_trend: RevenueTrendPoint[];
    conversion_funnel: FunnelStage[];
    license_mix: DistributionItem[];
    media_mix: DistributionItem[];
    top_assets: TopAsset[];
    marketplace_health: MarketplaceHealth;
    recent_activity: RecentMarketplaceActivity[];
};

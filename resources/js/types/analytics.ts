export type AnalyticsMetric = {
    value: number;
    previous_value: number;
    change_percent: number | null;
};

export type AnalyticsMetrics = Record<string, AnalyticsMetric>;

export type RevenueTrendPoint = {
    date: string;
    revenue_cents: number;
    orders_count: number;
};

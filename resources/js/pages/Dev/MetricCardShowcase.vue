<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Download,
    DollarSign,
    Heart,
    Image,
    ShoppingCart,
    Users,
} from '@lucide/vue';

import MetricCard from '@/Components/Shared/MetricCard.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import SectionHeader from '@/Components/Shared/SectionHeader.vue';
</script>

<template>
    <Head title="Metric Card Showcase" />

    <div class="space-y-10 p-6">
        <PageHeader
            eyebrow="Development"
            title="Metric Card Showcase"
            description="Examples of the reusable metric card component."
        />

        <section>
            <SectionHeader
                title="Standard Metrics"
                description="Common totals and counts."
            />

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <MetricCard
                    label="Users"
                    value="1,245"
                    trend="+34 this week"
                    trend-tone="positive"
                >
                    <template #icon>
                        <Users class="h-5 w-5" />
                    </template>
                </MetricCard>

                <MetricCard
                    label="Images"
                    value="8,420"
                    description="Published marketplace assets"
                >
                    <template #icon>
                        <Image class="h-5 w-5" />
                    </template>
                </MetricCard>

                <MetricCard
                    label="Downloads"
                    value="24,519"
                    trend="+8.1%"
                    trend-tone="info"
                >
                    <template #icon>
                        <Download class="h-5 w-5" />
                    </template>
                </MetricCard>
            </div>
        </section>

        <section>
            <SectionHeader
                title="Financial Metrics"
                description="Order and revenue examples."
            />

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <MetricCard
                    label="Revenue"
                    value="$18,425.00"
                    trend="+12.4%"
                    trend-tone="positive"
                    emphasized
                    size="lg"
                >
                    <template #icon>
                        <DollarSign class="h-5 w-5" />
                    </template>
                </MetricCard>

                <MetricCard
                    label="Orders"
                    :value="382"
                    trend="-2.3%"
                    trend-tone="negative"
                >
                    <template #icon>
                        <ShoppingCart class="h-5 w-5" />
                    </template>
                </MetricCard>

                <MetricCard
                    label="Favorites"
                    value="6,109"
                    trend="Stable"
                    trend-tone="neutral"
                >
                    <template #icon>
                        <Heart class="h-5 w-5" />
                    </template>
                </MetricCard>
            </div>
        </section>

        <section>
            <SectionHeader title="Sizes" />

            <div class="grid gap-4 sm:grid-cols-3">
                <MetricCard
                    label="Small"
                    value="125"
                    size="sm"
                />

                <MetricCard
                    label="Medium"
                    value="1,250"
                    size="md"
                />

                <MetricCard
                    label="Large"
                    value="12,500"
                    size="lg"
                />
            </div>
        </section>
    </div>
</template>

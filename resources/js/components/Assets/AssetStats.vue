<script setup lang="ts">
import SidebarCard from '@/Components/Shared/SidebarCard.vue';
import StatCard from '@/Components/Shared/StatCard.vue';

import { formatNumber } from '@/lib/formatNumber';

import type { AssetDetailData } from '@/types/asset';

defineProps<{
    asset: AssetDetailData;
}>();
</script>

<template>
    <SidebarCard title="Stats">
        <div class="grid grid-cols-2 gap-3">
            <StatCard
                label="Views"
                :value="formatNumber(asset.views_count)"
            />

            <StatCard
                label="Favorites"
                :value="formatNumber(asset.favorites_count)"
            />

            <StatCard
                label="Downloads"
                :value="formatNumber(asset.downloads_count)"
            />

            <StatCard
                label="Purchases"
                :value="formatNumber(asset.purchases_count)"
            />
        </div>
    </SidebarCard>
</template>

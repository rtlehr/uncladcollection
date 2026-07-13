<script setup lang="ts">
import SidebarCard from '@/Components/Shared/SidebarCard.vue';

import type { AssetDetailData } from '@/types/asset';

defineProps<{
    asset: AssetDetailData;
}>();
</script>

<template>
    <SidebarCard title="Asset Details">
        <dl class="space-y-4">
            <div>
                <dt class="text-sm font-medium text-muted-foreground">
                    Creator
                </dt>

                <dd class="mt-1">
                    {{ asset.photographer || '—' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-muted-foreground">
                    AI Generated
                </dt>

                <dd class="mt-1">
                    {{ asset.is_ai_generated ? 'Yes' : 'No' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-muted-foreground">
                    Added
                </dt>

                <dd class="mt-1">
                    {{ asset.created_at || '—' }}
                </dd>
            </div>

            <div v-if="asset.file_extension">
                <dt class="text-sm font-medium text-muted-foreground">
                    File Type
                </dt>

                <dd class="mt-1 uppercase">
                    {{ asset.file_extension }}
                </dd>
            </div>
        </dl>
    </SidebarCard>
</template>

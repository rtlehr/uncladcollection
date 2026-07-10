<script setup lang="ts">
import { Button } from '@/components/ui/button';

defineProps<{
    assetId: number;
    canDownload: boolean;
    isPurchased: boolean;
}>();
</script>

<template>
    <Button
        v-if="canDownload"
        as-child
    >
        <a :href="`/images/${assetId}/download`">
            Download
        </a>
    </Button>

    <Button
        v-else-if="isPurchased"
        disabled
        variant="secondary"
    >
        Purchased
    </Button>
</template>

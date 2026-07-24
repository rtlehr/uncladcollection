<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
const props = defineProps<{ status: string }>();
const label = computed(() => props.status.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()));
</script>
<template><Badge variant="outline">{{ label }}</Badge></template>

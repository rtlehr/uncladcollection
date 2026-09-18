<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import Form from './Form.vue';
defineProps<{xConfigured:boolean}>();
</script>
<template><Head title="Create X Post"/><div class="space-y-6 p-6"><div class="flex items-start justify-between"><PageHeader title="Create X Post" description="Write a draft or schedule it for automatic publishing."/><Button variant="outline" as-child><Link href="/admin/social-posts">Back</Link></Button></div><Form :x-configured="xConfigured"/></div></template>

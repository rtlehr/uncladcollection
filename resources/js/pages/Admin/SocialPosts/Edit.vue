<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PageHeader from '@/components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import Form from './Form.vue';
const props=defineProps<{socialPost:any; xConfigured:boolean}>();
function publishNow(){router.post(`/admin/social-posts/${props.socialPost.id}/publish-now`)}
</script>
<template><Head title="Edit X Post"/><div class="space-y-6 p-6"><div class="flex flex-wrap items-start justify-between gap-2"><PageHeader title="Edit X Post" :description="`Status: ${socialPost.status}`"/><div class="flex gap-2"><Button v-if="socialPost.status !== 'published' && socialPost.status !== 'publishing'" @click="publishNow">Post Now</Button><Button variant="outline" as-child><Link href="/admin/social-posts">Back</Link></Button></div></div><Form :social-post="socialPost" :x-configured="xConfigured"/></div></template>

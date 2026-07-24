<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/Shared/PageHeader.vue';
import EmptyState from '@/components/Shared/EmptyState.vue';
import Pagination from '@/components/Shared/Pagination.vue';
import StatusBadge from '@/components/Support/StatusBadge.vue';
import { Button } from '@/components/ui/button';
defineProps<{tickets:any}>();
</script>
<template><Head title="Support"/><div class="space-y-6 p-6"><PageHeader title="Support" description="Submit and track your support requests."><template #actions><Button as-child><Link href="/support/tickets/create">New request</Link></Button></template></PageHeader>
<div v-if="tickets.data.length" class="overflow-hidden rounded-xl border"><Link v-for="ticket in tickets.data" :key="ticket.uuid" :href="`/support/tickets/${ticket.uuid}`" class="grid gap-2 border-b p-5 transition hover:bg-muted/40 sm:grid-cols-[1fr_auto]"><div><p class="font-semibold">{{ ticket.subject }}</p><p class="text-sm text-muted-foreground">{{ ticket.ticket_number }} · {{ ticket.category || 'General' }}</p></div><div class="flex items-center gap-3"><StatusBadge :status="ticket.status"/><span class="text-sm text-muted-foreground">{{ new Date(ticket.updated_at).toLocaleDateString() }}</span></div></Link></div>
<EmptyState v-else title="No support requests" description="When you contact support, your requests and replies will appear here."/><Pagination v-if="tickets.links" :links="tickets.links" item-label="tickets"/></div></template>

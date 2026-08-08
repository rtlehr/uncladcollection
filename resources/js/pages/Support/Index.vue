<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AccountPageLayout from '@/components/Account/AccountPageLayout.vue';
import EmptyState from '@/components/Shared/EmptyState.vue';
import Pagination from '@/components/Shared/Pagination.vue';
import StatusBadge from '@/components/Support/StatusBadge.vue';
import { Button } from '@/components/ui/button';







defineProps<{tickets:any}>();
</script>


<template><Head title="My Tickets"/><AccountPageLayout><template #title>My Tickets</template><template #description>Track support requests, replies, and resolutions.</template><div class="space-y-6"><div class="flex justify-end"><Button as-child><Link href="/support#submit-request">New Request</Link></Button></div><div v-if="tickets.data.length" class="overflow-hidden rounded-xl border"><Link v-for="ticket in tickets.data" :key="ticket.uuid" :href="`/support/tickets/${ticket.uuid}`" class="grid gap-2 border-b p-5 transition hover:bg-muted/40 sm:grid-cols-[1fr_auto]"><div><p class="font-semibold">{{ticket.subject}}</p><p class="text-sm text-muted-foreground">{{ticket.ticket_number}} · {{ticket.category||'General'}}</p></div><div class="flex items-center gap-3"><StatusBadge :status="ticket.status"/><span class="text-sm text-muted-foreground">{{new Date(ticket.updated_at).toLocaleDateString()}}</span></div></Link></div><EmptyState v-else title="No support requests" description="When you contact support, your requests and replies will appear here."/><Pagination v-if="tickets.links" :links="tickets.links" item-label="tickets"/></div></AccountPageLayout></template>

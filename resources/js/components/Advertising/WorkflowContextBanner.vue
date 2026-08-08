<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Building2, ExternalLink } from '@lucide/vue';

const props = defineProps<{ context?: any | null; label?: string }>();
</script>

<template>
    <div v-if="props.context?.active && props.context?.advertiser" class="rounded-xl border border-primary/30 bg-primary/5 p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 rounded-lg bg-primary/10 p-2 text-primary">
                    <Building2 class="size-4" />
                </div>
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-primary">{{ label || 'Advertising workflow context' }}</div>
                    <div class="mt-0.5 font-semibold">Working with {{ props.context.advertiser.name }}</div>
                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                        <span v-if="props.context.advertiser.contact_name">Contact: {{ props.context.advertiser.contact_name }}</span>
                        <span v-if="props.context.advertiser.contact_email">{{ props.context.advertiser.contact_email }}</span>
                        <span v-if="props.context.lead">Opportunity: {{ props.context.lead.company_name }}</span>
                        <span v-if="props.context.campaign">Campaign: {{ props.context.campaign.name }}</span>
                    </div>
                    <p class="mt-2 text-sm text-muted-foreground">Client details are being carried forward from the advertiser workspace so you do not need to enter them again.</p>
                </div>
            </div>
            <Link :href="props.context.advertiser.workspace_href" class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
                Client workspace <ExternalLink class="size-3.5" />
            </Link>
        </div>
    </div>
</template>

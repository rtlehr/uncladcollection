<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    campaigns: any[];
}>();

const money = (value: number) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value / 100);
</script>

<template>
    <Head title="Advertising Campaigns" />

    <div class="space-y-6 p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <PageHeader
                title="Advertising Campaigns"
                description="Manage paid sponsorship and advertising campaigns separately from internal marketing promotions."
            />

            <Link href="/admin/ad-campaigns/create">
                <Button>Create Campaign</Button>
            </Link>
        </div>

        <div class="overflow-hidden rounded-xl border">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="p-3 text-left font-medium">Campaign</th>
                            <th class="p-3 text-left font-medium">Advertiser</th>
                            <th class="p-3 text-left font-medium">Status</th>
                            <th class="p-3 text-left font-medium">Placements</th>
                            <th class="p-3 text-left font-medium">Contract</th>
                            <th class="p-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="campaign in campaigns"
                            :key="campaign.id"
                            class="border-t"
                        >
                            <td class="p-3">
                                <Link
                                    :href="`/admin/ad-campaigns/${campaign.id}`"
                                    class="font-medium hover:underline"
                                >
                                    {{ campaign.name }}
                                </Link>
                                <div class="text-muted-foreground">
                                    {{ campaign.public_code }}
                                </div>
                            </td>

                            <td class="p-3">{{ campaign.advertiser.name }}</td>
                            <td class="p-3 capitalize">{{ campaign.status }}</td>
                            <td class="p-3">{{ campaign.placements.length }}</td>
                            <td class="p-3">{{ money(campaign.contract_value_cents) }}</td>

                            <td class="p-3">
                                <div class="flex justify-end gap-2">
                                    <Link :href="`/admin/ad-campaigns/${campaign.id}`">
                                        <Button size="sm" variant="outline">View</Button>
                                    </Link>

                                    <Link :href="`/admin/ad-campaigns/${campaign.id}/creatives`">
                                        <Button size="sm">Creatives</Button>
                                    </Link>

                                    <Link :href="`/admin/ad-campaigns/${campaign.id}/edit`">
                                        <Button size="sm" variant="outline">Edit</Button>
                                    </Link>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="campaigns.length === 0">
                            <td colspan="6" class="p-8 text-center text-muted-foreground">
                                No advertising campaigns have been created.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

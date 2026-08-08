<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    campaign: any | null;
    advertisers: any[];
    placements: any[];
}>();

const form = useForm({
    advertiser_id: props.campaign?.advertiser_id ?? '',
    name: props.campaign?.name ?? '',
    objective: props.campaign?.objective ?? 'awareness',
    pricing_model: props.campaign?.pricing_model ?? 'flat',
    budget_cents: props.campaign?.budget_cents ?? 0,
    contract_value_cents: props.campaign?.contract_value_cents ?? 0,
    impression_goal: props.campaign?.impression_goal ?? null,
    click_goal: props.campaign?.click_goal ?? null,
    starts_at: props.campaign?.starts_at?.slice(0, 16) ?? '',
    ends_at: props.campaign?.ends_at?.slice(0, 16) ?? '',
    internal_notes: props.campaign?.internal_notes ?? '',
    placement_ids: props.campaign?.placements?.map((placement: any) => placement.id) ?? [],
});

const save = () => {
    if (props.campaign) {
        form.put(`/admin/ad-campaigns/${props.campaign.id}`);

        return;
    }

    form.post('/admin/ad-campaigns');
};
</script>

<template>
    <Head :title="campaign ? 'Edit Advertising Campaign' : 'Create Advertising Campaign'" />

    <form class="space-y-6 p-6" @submit.prevent="save">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <PageHeader
                :title="campaign ? 'Edit Advertising Campaign' : 'Create Advertising Campaign'"
                description="Campaign terms, goals, schedule, and eligible inventory."
            />

            <div v-if="campaign" class="flex flex-wrap gap-2">
                <Link :href="`/admin/ad-campaigns/${campaign.id}`">
                    <Button type="button" variant="outline">View Campaign</Button>
                </Link>

                <Link :href="`/admin/ad-campaigns/${campaign.id}/creatives`">
                    <Button type="button">Manage Creatives</Button>
                </Link>
            </div>
        </div>

        <div class="grid gap-4 rounded-xl border p-5 md:grid-cols-2">
            <label>
                Advertiser
                <select
                    v-model="form.advertiser_id"
                    class="h-10 w-full rounded-md border bg-background px-3"
                >
                    <option value="">Select advertiser</option>
                    <option
                        v-for="advertiser in advertisers"
                        :key="advertiser.id"
                        :value="advertiser.id"
                    >
                        {{ advertiser.name }}
                    </option>
                </select>
            </label>

            <label>
                Campaign name
                <Input v-model="form.name" />
            </label>

            <label>
                Objective
                <select
                    v-model="form.objective"
                    class="h-10 w-full rounded-md border bg-background px-3"
                >
                    <option value="awareness">Awareness</option>
                    <option value="traffic">Traffic</option>
                    <option value="conversion">Conversion</option>
                    <option value="sponsorship">Sponsorship</option>
                </select>
            </label>

            <label>
                Pricing model
                <select
                    v-model="form.pricing_model"
                    class="h-10 w-full rounded-md border bg-background px-3"
                >
                    <option value="flat">Flat</option>
                    <option value="cpm">CPM</option>
                    <option value="cpc">CPC</option>
                    <option value="sponsorship">Sponsorship</option>
                </select>
            </label>

            <label>
                Budget (cents)
                <Input v-model="form.budget_cents" type="number" />
            </label>

            <label>
                Contract value (cents)
                <Input v-model="form.contract_value_cents" type="number" />
            </label>

            <label>
                Impression goal
                <Input v-model="form.impression_goal" type="number" />
            </label>

            <label>
                Click goal
                <Input v-model="form.click_goal" type="number" />
            </label>

            <label>
                Starts
                <Input v-model="form.starts_at" type="datetime-local" />
            </label>

            <label>
                Ends
                <Input v-model="form.ends_at" type="datetime-local" />
            </label>

            <fieldset class="md:col-span-2">
                <legend class="mb-2 font-medium">Placements</legend>
                <div class="grid gap-2 md:grid-cols-3">
                    <label
                        v-for="placement in placements"
                        :key="placement.id"
                        class="flex gap-2 rounded-md border p-3"
                    >
                        <input
                            v-model="form.placement_ids"
                            type="checkbox"
                            :value="placement.id"
                        />
                        {{ placement.name }}
                    </label>
                </div>
            </fieldset>

            <label class="md:col-span-2">
                Internal notes
                <textarea
                    v-model="form.internal_notes"
                    class="min-h-24 w-full rounded-md border bg-background p-3"
                />
            </label>
        </div>

        <div class="flex flex-wrap gap-2">
            <Button :disabled="form.processing">
                {{ form.processing ? 'Saving…' : 'Save Campaign' }}
            </Button>

            <Link v-if="campaign" :href="`/admin/ad-campaigns/${campaign.id}`">
                <Button type="button" variant="outline">Cancel</Button>
            </Link>
        </div>
    </form>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import WorkflowContextBanner from '@/components/Advertising/WorkflowContextBanner.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    campaign: any | null;
    advertisers: any[];
    placements: any[];
    selectedAdvertiserId?: number | null;
    workflowContext?: any;
}>();

const form = useForm({
    advertiser_id: props.campaign?.advertiser_id ?? props.selectedAdvertiserId ?? '',
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
    placement_priorities: Object.fromEntries(
        props.placements.map((placement: any) => {
            const assigned = props.campaign?.placements?.find((item: any) => item.id === placement.id);

            return [placement.id, Number(assigned?.pivot?.priority ?? 50)];
        }),
    ),
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

        <WorkflowContextBanner :context="workflowContext" label="Campaign workflow" />

        <div v-if="Object.keys(form.errors).length" class="rounded-xl border border-destructive/40 bg-destructive/5 p-4">
            <p class="font-medium text-destructive">Campaign could not be saved.</p>
            <p class="mt-1 text-sm text-muted-foreground">Correct the highlighted field(s) below and save again.</p>
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
                <InputError :message="form.errors.advertiser_id" />
            </label>

            <label>
                Campaign name
                <Input v-model="form.name" />
                <InputError :message="form.errors.name" />
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
                <InputError :message="form.errors.objective" />
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
                <InputError :message="form.errors.pricing_model" />
            </label>

            <label>
                Budget (cents)
                <Input v-model="form.budget_cents" type="number" />
                <InputError :message="form.errors.budget_cents" />
            </label>

            <label>
                Contract value (cents)
                <Input v-model="form.contract_value_cents" type="number" />
                <InputError :message="form.errors.contract_value_cents" />
            </label>

            <label>
                Impression goal
                <Input v-model="form.impression_goal" type="number" />
                <InputError :message="form.errors.impression_goal" />
            </label>

            <label>
                Click goal
                <Input v-model="form.click_goal" type="number" />
                <InputError :message="form.errors.click_goal" />
            </label>

            <label>
                Starts
                <Input v-model="form.starts_at" type="datetime-local" />
                <InputError :message="form.errors.starts_at" />
            </label>

            <label>
                Ends
                <Input v-model="form.ends_at" type="datetime-local" />
                <InputError :message="form.errors.ends_at" />
            </label>

            <fieldset class="md:col-span-2">
                <legend class="font-medium">Placements & rotation weight</legend>
                <p class="mt-1 text-sm text-muted-foreground">
                    Rotation weight controls how often this campaign is selected relative to other eligible campaigns in the same placement.
                    Equal weights rotate approximately evenly. A weight of 75 versus 25 is roughly a 75% / 25% split when both campaigns are eligible.
                </p>

                <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="placement in placements"
                        :key="placement.id"
                        class="rounded-lg border p-3"
                    >
                        <label class="flex items-start gap-2">
                            <input
                                v-model="form.placement_ids"
                                type="checkbox"
                                :value="placement.id"
                                class="mt-1"
                            />
                            <span>
                                <span class="block font-medium">{{ placement.name }}</span>
                                <span class="text-sm text-muted-foreground">{{ placement.location }} / {{ placement.format }}</span>
                            </span>
                        </label>

                        <label
                            v-if="form.placement_ids.includes(placement.id)"
                            class="mt-3 block border-t pt-3"
                        >
                            <span class="text-sm font-medium">Rotation weight</span>
                            <Input
                                v-model.number="form.placement_priorities[placement.id]"
                                type="number"
                                min="1"
                                max="100"
                                step="1"
                                class="mt-1"
                            />
                            <span class="mt-1 block text-xs text-muted-foreground">1–100. Default: 50.</span>
                            <InputError :message="form.errors[`placement_priorities.${placement.id}`]" />
                        </label>
                    </div>
                </div>

                <InputError :message="form.errors.placement_ids" />
                <InputError :message="form.errors.placement_priorities" />
            </fieldset>

            <label class="md:col-span-2">
                Internal notes
                <textarea
                    v-model="form.internal_notes"
                    class="min-h-24 w-full rounded-md border bg-background p-3"
                />
                <InputError :message="form.errors.internal_notes" />
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

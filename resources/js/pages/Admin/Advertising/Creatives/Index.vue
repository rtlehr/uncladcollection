<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    campaign: any;
    creatives: any[];
}>();

const submit = (creative: any) =>
    router.post(
        `/admin/ad-campaigns/${props.campaign.id}/creatives/${creative.id}/submit`,
    );

const decide = (creative: any, decision: 'approve' | 'reject') => {
    const rejectionReason =
        decision === 'reject'
            ? prompt('Rejection reason') || 'Rejected'
            : '';

    router.post(
        `/admin/ad-campaigns/${props.campaign.id}/creatives/${creative.id}/decision`,
        {
            decision,
            rejection_reason: rejectionReason,
        },
    );
};

const returnToDraft = (creative: any) => {
    if (
        !confirm(
            'Return this creative to draft? It will immediately stop appearing in public ad rotation.',
        )
    ) {
        return;
    }

    router.post(
        `/admin/ad-campaigns/${props.campaign.id}/creatives/${creative.id}/return-to-draft`,
    );
};

const remove = (creative: any) => {
    if (!confirm('Delete this creative?')) {
        return;
    }

    router.delete(
        `/admin/ad-campaigns/${props.campaign.id}/creatives/${creative.id}`,
    );
};
</script>

<template>
    <Head title="Advertising Creatives" />

    <div class="space-y-6 p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <PageHeader
                title="Advertising Creatives"
                :description="`${campaign.name} · ${campaign.advertiser.name}`"
            />

            <div class="flex flex-wrap gap-2">
                <Link :href="`/admin/ad-campaigns/${campaign.id}`">
                    <Button variant="outline">Campaign</Button>
                </Link>

                <Link :href="`/admin/ad-campaigns/${campaign.id}/creatives/create`">
                    <Button>Add Creative</Button>
                </Link>
            </div>
        </div>

        <div
            v-if="!creatives.length"
            class="rounded-xl border border-dashed p-10 text-center text-muted-foreground"
        >
            No creatives yet. Add the first placement-ready image or video.
        </div>

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="creative in creatives"
                :key="creative.id"
                class="overflow-hidden rounded-xl border"
            >
                <div class="aspect-video bg-muted">
                    <img
                        v-if="creative.creative_type === 'image'"
                        :src="creative.media_url"
                        :alt="creative.alt_text || creative.name"
                        class="h-full w-full object-contain"
                    />

                    <video
                        v-else
                        :src="creative.media_url"
                        controls
                        class="h-full w-full"
                    />
                </div>

                <div class="space-y-3 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold">{{ creative.name }}</h2>
                            <p class="text-sm text-muted-foreground">
                                {{ creative.placements?.map((placement: any) => placement.name).join(', ') || creative.placement?.name || 'No placement' }}
                                · {{ creative.width || '—' }} ×
                                {{ creative.height || '—' }}
                            </p>
                        </div>

                        <span
                            class="rounded-full border px-2 py-1 text-xs capitalize"
                        >
                            {{ creative.status }}
                        </span>
                    </div>

                    <p
                        v-if="!creative.is_placement_compatible"
                        class="text-sm text-destructive"
                    >
                        Dimensions do not match one or more selected placements.
                    </p>

                    <p
                        v-if="creative.rejection_reason"
                        class="text-sm text-destructive"
                    >
                        {{ creative.rejection_reason }}
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-if="creative.status !== 'approved'"
                            :href="`/admin/ad-campaigns/${campaign.id}/creatives/${creative.id}/edit`"
                        >
                            <Button size="sm" variant="outline">Edit</Button>
                        </Link>

                        <Button
                            v-if="['draft', 'rejected'].includes(creative.status)"
                            size="sm"
                            @click="submit(creative)"
                        >
                            Submit
                        </Button>

                        <Button
                            v-if="creative.status === 'submitted'"
                            size="sm"
                            @click="decide(creative, 'approve')"
                        >
                            Approve
                        </Button>

                        <Button
                            v-if="creative.status === 'submitted'"
                            size="sm"
                            variant="destructive"
                            @click="decide(creative, 'reject')"
                        >
                            Reject
                        </Button>

                        <Button
                            v-if="creative.status === 'approved'"
                            size="sm"
                            variant="outline"
                            @click="returnToDraft(creative)"
                        >
                            Return to Draft
                        </Button>

                        <Button
                            v-if="creative.status !== 'approved'"
                            size="sm"
                            variant="ghost"
                            @click="remove(creative)"
                        >
                            Delete
                        </Button>
                    </div>
                </div>
            </article>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageHeader from '@/Components/Shared/PageHeader.vue';
import SectionHeader from '@/Components/Shared/SectionHeader.vue';
import StatusBadge from '@/Components/Shared/StatusBadge.vue';

const groups = [
    { title: 'Content', statuses: ['draft', 'published', 'scheduled', 'featured'] },
    { title: 'Comments', statuses: ['pending', 'approved', 'hidden', 'spam', 'deleted', 'reviewed', 'dismissed'] },
    { title: 'Orders', statuses: ['paid', 'failed', 'canceled', 'refunded', 'partially_refunded'] },
    { title: 'Users and Assets', statuses: ['active', 'disabled', 'member', 'editor', 'moderator', 'admin', 'ai_generated', 'purchased', 'downloadable'] },
];
</script>

<template>
    <Head title="Status Badge Showcase" />

    <div class="space-y-10 p-6">
        <PageHeader
            eyebrow="Development"
            title="Status Badge Showcase"
            description="Examples of the reusable status badge system."
        />

        <section v-for="group in groups" :key="group.title">
            <SectionHeader :title="group.title" />

            <div class="flex flex-wrap gap-3">
                <StatusBadge
                    v-for="status in group.statuses"
                    :key="status"
                    :status="status"
                />
            </div>
        </section>

        <section>
            <SectionHeader title="Overrides and Fallbacks" />

            <div class="flex flex-wrap gap-3">
                <StatusBadge status="published" label="Live" />
                <StatusBadge status="custom_status" />
                <StatusBadge status="pending" tone="info" size="md" />
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import LicenseTypeForm from '@/components/admin/LicenseTypeForm.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';

const form = useForm({
    name: '',
    slug: '',
    description: '',
    image_unit_price: '0.00',
    video_unit_price: '0.00',
    minimum_price: '',
    currency: 'USD',
    download_limit: null as number | null,
    expires_after_days: null as number | null,
    max_resolution: 'high_res',
    usage_terms: '',
    is_active: true,
    sort_order: 0,
});

function submit() {
    form.post('/admin/license-types', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Create License Type" />

    <AppLayout>
        <div class="space-y-6 p-6">
            <PageHeader
                title="Create License Type"
                description="Create a reusable license with per-image and per-video pricing."
            />

            <FormSection
                title="License Details"
                description="Configure unit pricing, minimums, download limits, resolution, and usage terms."
            >
                <LicenseTypeForm
                    :form="form"
                    submit-label="Create License Type"
                    @submit="submit"
                />
            </FormSection>
        </div>
    </AppLayout>
</template>

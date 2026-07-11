<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import LicenseTypeForm from '@/components/admin/LicenseTypeForm.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';

import type { EditableAdminLicenseType } from '@/types/licenseType';

const props = defineProps<{
    licenseType: EditableAdminLicenseType;
}>();

const form = useForm({
    name: props.licenseType.name ?? '',
    slug: props.licenseType.slug ?? '',
    description: props.licenseType.description ?? '',
    price: props.licenseType.price ?? '0.00',
    currency: props.licenseType.currency ?? 'USD',
    download_limit: props.licenseType.download_limit,
    expires_after_days: props.licenseType.expires_after_days,
    max_resolution: props.licenseType.max_resolution ?? 'high_res',
    usage_terms: props.licenseType.usage_terms ?? '',
    is_active: props.licenseType.is_active ?? true,
    sort_order: props.licenseType.sort_order ?? 0,
});

function submit() {
    form.put(`/admin/license-types/${props.licenseType.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Edit License Type" />

    <AppLayout>
        <div class="space-y-6 p-6">
            <PageHeader
                title="Edit License Type"
                description="Update this licensing option."
            />

            <FormSection
                title="License Details"
                description="Update pricing, download limits, resolution, and usage terms."
            >
                <LicenseTypeForm
                    :form="form"
                    submit-label="Update License Type"
                    @submit="submit"
                />
            </FormSection>
        </div>
    </AppLayout>
</template>

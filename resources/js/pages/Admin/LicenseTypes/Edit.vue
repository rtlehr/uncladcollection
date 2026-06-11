<script setup lang="ts">
import LicenseTypeForm from '@/components/admin/LicenseTypeForm.vue';
import { Head, useForm } from '@inertiajs/vue3';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface LicenseType {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    currency: string;
    download_limit: number | null;
    expires_after_days: number | null;
    max_resolution: string;
    usage_terms: string | null;
    is_active: boolean;
    sort_order: number;
}

const props = defineProps<{
    licenseType: LicenseType;
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
    form.put(`/admin/license-types/${props.licenseType.id}`);
}
</script>

<template>
    <Head title="Edit License Type" />

    <AppLayout>
        <div class="space-y-6 p-6">

            <div>
                <h1 class="text-2xl font-bold">
                    Edit License Type
                </h1>

                <p class="text-muted-foreground">
                    Update this licensing option.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>
                        License Details
                    </CardTitle>
                </CardHeader>

                <CardContent>
                    <LicenseTypeForm
                        :form="form"
                        submit-label="Update License Type"
                        @submit="submit"
                    />
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
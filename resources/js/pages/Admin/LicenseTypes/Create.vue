<script setup lang="ts">
import LicenseTypeForm from '@/components/admin/LicenseTypeForm.vue';
import { Head, useForm } from '@inertiajs/vue3';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

const form = useForm({
    name: '',
    slug: '',
    description: '',
    price: '0.00',
    currency: 'USD',
    download_limit: null as number | null,
    expires_after_days: null as number | null,
    max_resolution: 'high_res',
    usage_terms: '',
    is_active: true,
    sort_order: 0,
});

function submit() {
    form.post('/admin/license-types');
}
</script>

<template>
    <Head title="Create License Type" />

    <AppLayout>
        <div class="space-y-6 p-6">

            <div>
                <h1 class="text-2xl font-bold">
                    Create License Type
                </h1>

                <p class="text-muted-foreground">
                    Create a new image licensing option.
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
                        submit-label="Create License Type"
                        @submit="submit"
                    />
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
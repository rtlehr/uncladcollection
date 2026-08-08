<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';

const props = defineProps<{
    assetId: number;
    selectedLicenseTypeId: number | null;
    isLoggedIn: boolean;
}>();

function purchase() {
    if (!props.isLoggedIn) {
        window.location.href = '/login';

        return;
    }

    if (!props.selectedLicenseTypeId) {
        return;
    }

    const form = document.createElement('form');

    form.method = 'POST';
    form.action = `/checkout/${props.assetId}`;

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    if (csrfToken) {
        const csrfInput = document.createElement('input');

        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;

        form.appendChild(csrfInput);
    }

    const licenseInput = document.createElement('input');

    licenseInput.type = 'hidden';
    licenseInput.name = 'license_type_id';
    licenseInput.value = String(props.selectedLicenseTypeId);

    form.appendChild(licenseInput);

    document.body.appendChild(form);
    form.submit();
}

function addToCart() {
    if (!props.isLoggedIn) {
        router.visit('/login');

        return;
    }

    if (!props.selectedLicenseTypeId) {
        return;
    }

    router.post(
        '/cart/items',
        {
            image_id: props.assetId,
            license_type_id: props.selectedLicenseTypeId,
        },
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <div class="flex gap-2">
        <Button
            type="button"
            :disabled="!selectedLicenseTypeId"
            @click="purchase"
        >
            Buy Now
        </Button>

        <Button
            type="button"
            variant="outline"
            :disabled="!selectedLicenseTypeId"
            @click="addToCart"
        >
            Add to Cart
        </Button>
    </div>
</template>

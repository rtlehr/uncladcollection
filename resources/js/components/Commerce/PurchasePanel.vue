<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import {
    LoaderCircle,
    ShoppingCart,
    Zap,
} from '@lucide/vue';
import { computed, ref } from 'vue';

import LicenseSelector from '@/components/Commerce/LicenseSelector.vue';

import type { GalleryLicenseType } from '@/types/gallery';

const props = defineProps<{
    imageId: number;
    imageTitle: string;
    licenses: GalleryLicenseType[];
    canPurchase: boolean;
    isPurchased: boolean;
}>();

const page = usePage();
const selectedLicenseId = ref<number | null>(
    props.licenses[0]?.id ?? null,
);
const addingToCart = ref(false);
const buyingNow = ref(false);

const isAuthenticated = computed(() =>
    Boolean((page.props.auth as any)?.user),
);

const hasLicenses = computed(() =>
    props.licenses.length > 0,
);

function requireAuthentication(): boolean {
    if (isAuthenticated.value) {
        return true;
    }

    router.visit('/login');

    return false;
}

function addToCart(): void {
    if (
        ! requireAuthentication()
        || ! selectedLicenseId.value
        || addingToCart.value
    ) {
        return;
    }

    addingToCart.value = true;

    router.post(
        '/cart/items',
        {
            image_id: props.imageId,
            license_type_id: selectedLicenseId.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                addingToCart.value = false;
            },
        },
    );
}

function buyNow(): void {
    if (
        ! requireAuthentication()
        || ! selectedLicenseId.value
        || buyingNow.value
    ) {
        return;
    }

    buyingNow.value = true;

    router.post(
        `/checkout/${props.imageId}`,
        {
            license_type_id: selectedLicenseId.value,
        },
        {
            onFinish: () => {
                buyingNow.value = false;
            },
        },
    );
}
</script>

<template>
    <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
        <h2 class="text-xl font-semibold">
            License this image
        </h2>

        <p class="mt-2 text-sm leading-6 text-stone-600 dark:text-stone-400">
            Choose the license that best fits your project.
        </p>

        <div
            v-if="isPurchased"
            class="mt-5 rounded-2xl bg-emerald-500/10 px-4 py-4 text-sm font-medium text-emerald-700 dark:text-emerald-300"
        >
            You already own an active license for this image.
        </div>

        <template v-else-if="canPurchase && hasLicenses">
            <div class="mt-5">
                <LicenseSelector
                    v-model="selectedLicenseId"
                    :licenses="licenses"
                />
            </div>

            <div class="mt-5 grid gap-3">
                <button
                    type="button"
                    class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-full border border-[var(--brand-primary)] px-5 text-sm font-semibold text-[var(--brand-primary)] transition hover:bg-[color-mix(in_srgb,var(--brand-primary)_7%,transparent)] disabled:cursor-not-allowed disabled:opacity-50 dark:border-stone-500 dark:text-white"
                    :disabled="
                        !selectedLicenseId
                        || addingToCart
                        || buyingNow
                    "
                    @click="addToCart"
                >
                    <LoaderCircle
                        v-if="addingToCart"
                        class="h-4 w-4 animate-spin"
                    />

                    <ShoppingCart
                        v-else
                        class="h-4 w-4"
                    />

                    {{
                        addingToCart
                            ? 'Adding to Cart...'
                            : 'Add to Cart'
                    }}
                </button>

                <div class="flex items-center gap-3" aria-hidden="true">
                    <span class="h-px flex-1 bg-stone-200 dark:bg-stone-800" />
                    <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-stone-400">
                        or
                    </span>
                    <span class="h-px flex-1 bg-stone-200 dark:bg-stone-800" />
                </div>

                <button
                    type="button"
                    class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="
                        !selectedLicenseId
                        || addingToCart
                        || buyingNow
                    "
                    @click="buyNow"
                >
                    <LoaderCircle
                        v-if="buyingNow"
                        class="h-4 w-4 animate-spin"
                    />

                    <Zap
                        v-else
                        class="h-4 w-4"
                    />

                    {{
                        buyingNow
                            ? 'Opening Checkout...'
                            : 'Buy Now'
                    }}
                </button>
            </div>

            <p class="mt-4 text-center text-xs leading-5 text-stone-500 dark:text-stone-400">
                Add to Cart to continue browsing, or Buy Now to go directly to secure Stripe Checkout.
            </p>
        </template>

        <div
            v-else
            class="mt-5 rounded-2xl bg-stone-100 px-4 py-4 text-sm text-stone-600 dark:bg-stone-800 dark:text-stone-300"
        >
            No active license options are currently available for this image.
        </div>
    </div>
</template>

<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';

export default {
    layout: PublicBlankLayout,
};
</script>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ShieldCheck,
    ShoppingCart,
    Trash2,
} from '@lucide/vue';

import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';

type CartImage = {
    id: number;
    title: string;
    slug: string;
    thumbnail_url: string | null;
    icon_url: string | null;
    preview_url: string | null;
};

type CartLicenseType = {
    id: number;
    name: string;
    description: string | null;
    price_cents: number;
    currency: string;
};

type CartItem = {
    id: number;
    price_cents: number;
    currency: string;
    image: CartImage;
    license_type: CartLicenseType;
};

const props = defineProps<{
    cartItems: CartItem[];
    cartTotalCents: number;
}>();

function formatPrice(
    priceCents: number,
    currency = 'USD',
): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
    }).format(priceCents / 100);
}

function removeItem(cartItem: CartItem): void {
    router.delete(`/cart/items/${cartItem.id}`, {
        preserveScroll: true,
    });
}

function clearCart(): void {
    router.delete('/cart', {
        preserveScroll: true,
    });
}

function checkout(): void {
    router.post('/cart/checkout');
}
</script>

<template>
    <Head title="Shopping Cart" />

    <PublicPageLayout>
        <section class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
            <div class="mx-auto max-w-[1280px] px-4 py-10 sm:px-8 sm:py-14 lg:px-12">
                <Link
                    href="/images"
                    class="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-stone-500 hover:text-[var(--brand-accent)]"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Continue browsing
                </Link>

                <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]">
                            Secure checkout
                        </p>

                        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-5xl">
                            Shopping Cart
                        </h1>

                        <p class="mt-3 text-sm leading-6 text-stone-600 dark:text-stone-400">
                            Review your image licenses before checkout.
                        </p>
                    </div>

                    <button
                        v-if="cartItems.length"
                        type="button"
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full border border-stone-300 px-5 text-sm font-semibold dark:border-stone-700"
                        @click="clearCart"
                    >
                        <Trash2 class="h-4 w-4" />
                        Clear Cart
                    </button>
                </div>
            </div>
        </section>

        <main class="mx-auto max-w-[1280px] px-4 py-8 pb-28 sm:px-8 sm:py-12 lg:px-12 lg:pb-12">
            <div
                v-if="!cartItems.length"
                class="rounded-3xl border border-dashed border-stone-300 px-5 py-14 text-center dark:border-stone-700"
            >
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-stone-200 dark:bg-stone-800">
                    <ShoppingCart class="h-7 w-7 text-stone-500" />
                </div>

                <h2 class="mt-5 text-xl font-semibold">
                    Your cart is empty
                </h2>

                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600 dark:text-stone-400">
                    Browse the image library and choose a license to begin.
                </p>

                <Link
                    href="/images"
                    class="mt-6 inline-flex min-h-11 items-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white"
                >
                    Browse Images
                </Link>
            </div>

            <div
                v-else
                class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]"
            >
                <section class="space-y-4">
                    <article
                        v-for="cartItem in cartItems"
                        :key="cartItem.id"
                        class="grid gap-4 rounded-2xl border border-stone-200 bg-white p-4 sm:grid-cols-[150px_minmax(0,1fr)] sm:rounded-3xl dark:border-stone-800 dark:bg-stone-900"
                    >
                        <Link
                            :href="`/images/${cartItem.image.slug}`"
                            class="block overflow-hidden rounded-xl bg-stone-200 dark:bg-stone-800"
                        >
                            <img
                                v-if="cartItem.image.preview_url"
                                :src="cartItem.image.preview_url"
                                :alt="cartItem.image.title"
                                class="aspect-[4/3] w-full object-cover sm:h-full"
                            />
                        </Link>

                        <div class="flex min-w-0 flex-col justify-between gap-4">
                            <div>
                                <Link
                                    :href="`/images/${cartItem.image.slug}`"
                                    class="public-break-anywhere text-lg font-semibold hover:text-[var(--brand-accent)]"
                                >
                                    {{ cartItem.image.title }}
                                </Link>

                                <div class="mt-2 text-sm font-medium">
                                    {{ cartItem.license_type.name }}
                                </div>

                                <p
                                    v-if="cartItem.license_type.description"
                                    class="mt-2 line-clamp-3 text-sm leading-6 text-stone-600 dark:text-stone-400"
                                >
                                    {{ cartItem.license_type.description }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <span class="text-lg font-semibold">
                                    {{ formatPrice(cartItem.price_cents, cartItem.currency) }}
                                </span>

                                <button
                                    type="button"
                                    class="public-touch-target inline-flex h-11 w-11 items-center justify-center rounded-full border border-stone-300 text-stone-500 hover:text-red-600 dark:border-stone-700"
                                    :aria-label="`Remove ${cartItem.image.title}`"
                                    @click="removeItem(cartItem)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </article>
                </section>

                <aside class="lg:sticky lg:top-24 lg:self-start">
                    <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6 dark:border-stone-800 dark:bg-stone-900">
                        <h2 class="text-xl font-semibold">
                            Order Summary
                        </h2>

                        <div class="mt-5 flex items-center justify-between border-b border-stone-200 pb-4 text-sm dark:border-stone-800">
                            <span class="text-stone-500">
                                {{ cartItems.length }}
                                {{ cartItems.length === 1 ? 'license' : 'licenses' }}
                            </span>

                            <span class="font-medium">
                                {{ formatPrice(cartTotalCents) }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <span class="font-semibold">
                                Total
                            </span>

                            <span class="text-2xl font-semibold">
                                {{ formatPrice(cartTotalCents) }}
                            </span>
                        </div>

                        <button
                            type="button"
                            class="mt-6 hidden h-12 w-full items-center justify-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white lg:inline-flex"
                            @click="checkout"
                        >
                            Continue to Checkout
                        </button>

                        <p class="mt-4 flex items-start gap-2 text-xs leading-5 text-stone-500 dark:text-stone-400">
                            <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0" />
                            Secure payment processing through Stripe.
                        </p>
                    </div>
                </aside>
            </div>
        </main>

        <div
            v-if="cartItems.length"
            class="safe-bottom fixed inset-x-0 bottom-0 z-40 border-t border-stone-200 bg-white/95 px-3 pt-3 backdrop-blur lg:hidden dark:border-stone-800 dark:bg-stone-950/95"
        >
            <button
                type="button"
                class="inline-flex h-12 w-full items-center justify-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white"
                @click="checkout"
            >
                Checkout · {{ formatPrice(cartTotalCents) }}
            </button>
        </div>
    </PublicPageLayout>
</template>

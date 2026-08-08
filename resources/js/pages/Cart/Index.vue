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
    Minus,
    Plus,
    ShieldCheck,
    ShoppingCart,
    Trash2,
} from '@lucide/vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';
import { Button } from '@/components/ui/button';






const csrfToken =
    document
        .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.content ?? '';

type ConfigurationLabel = {
    group: string;
    values: string[];
};

type CartItem = {
    id: number;
    kind: 'asset' | 'legacy_image';
    quantity: number;
    unit_price_cents: number;
    line_total_cents: number;
    currency: string;

    configuration: {
        labels?: ConfigurationLabel[];
    } | null;

    shipping_address?: { full_name?: string; address_line_1?: string; address_line_2?: string | null; city?: string; region?: string; postal_code?: string; country_code?: string } | null;
    pricing: {
        pricing_tier_label?: string | null;
    } | null;

    asset?: {
        id: number;
        title: string;
        slug: string;
        preview_url: string | null;
    };

    offering?: {
        id: number;
        name: string;
        description: string | null;
    };

    image?: {
        id: number;
        title: string;
        slug: string;
        preview_url: string | null;
    };

    license_type?: {
        id: number;
        name: string;
        description: string | null;
    };
};

const props = defineProps<{
    cartItems: CartItem[];
    cartTotalCents: number;
    containsAssetItems: boolean;
}>();

function formatPrice(
    cents: number,
    currency = 'USD',
): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
    }).format(cents / 100);
}

function itemTitle(item: CartItem): string {
    return (
        item.asset?.title
        ?? item.image?.title
        ?? 'Marketplace item'
    );
}

function itemHref(item: CartItem): string {
    if (item.kind === 'asset') {
        return `/assets/${item.asset?.slug}`;
    }

    return `/images/${item.image?.slug}`;
}

function itemPreview(item: CartItem): string | null {
    return (
        item.asset?.preview_url
        ?? item.image?.preview_url
        ?? null
    );
}

function packageName(item: CartItem): string {
    return (
        item.offering?.name
        ?? item.license_type?.name
        ?? 'License'
    );
}

function totalQuantity(): number {
    return props.cartItems.reduce(
        (sum, item) => sum + item.quantity,
        0,
    );
}

function updateQuantity(
    item: CartItem,
    quantity: number,
): void {
    if (
        item.kind !== 'asset'
        || quantity < 1
        || quantity > 999
    ) {
        return;
    }

    router.patch(
        `/cart/items/${item.id}`,
        { quantity },
        {
            preserveScroll: true,
        },
    );
}

function removeItem(item: CartItem): void {
    router.delete(
        `/cart/items/${item.id}`,
        {
            preserveScroll: true,
        },
    );
}

function clearCart(): void {
    router.delete(
        '/cart',
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head title="Shopping Cart" />

    <PublicPageLayout>
        <section
            class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900"
        >
            <div
                class="mx-auto max-w-[1280px] px-4 py-10 sm:px-8 lg:px-12"
            >
                <Link
                    href="/images"
                    class="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-stone-500 transition hover:text-[var(--brand-accent)]"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Continue browsing
                </Link>

                <div
                    class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]"
                        >
                            Secure checkout
                        </p>

                        <h1
                            class="mt-3 text-3xl font-semibold sm:text-5xl"
                        >
                            Shopping Cart
                        </h1>

                        <p
                            class="mt-3 text-sm text-stone-600 dark:text-stone-400"
                        >
                            Review configured assets, quantities, and
                            license packages.
                        </p>
                    </div>

                    <button
                        v-if="cartItems.length"
                        type="button"
                        class="min-h-11 rounded-full border border-stone-300 px-5 text-sm font-semibold transition hover:bg-stone-50 dark:border-stone-700 dark:hover:bg-stone-800"
                        @click="clearCart"
                    >
                        Clear Cart
                    </button>
                </div>
            </div>
        </section>

        <main
            class="mx-auto max-w-[1280px] px-4 py-10 sm:px-8 lg:px-12"
        >
            <div
                v-if="!cartItems.length"
                class="rounded-3xl border border-dashed border-stone-300 p-14 text-center dark:border-stone-700"
            >
                <ShoppingCart
                    class="mx-auto h-8 w-8 text-stone-500"
                />

                <h2 class="mt-4 text-xl font-semibold">
                    Your cart is empty
                </h2>

                <p class="mt-2 text-sm text-stone-500">
                    Browse the marketplace and configure an asset to
                    begin.
                </p>

                <Link
                    href="/images"
                    class="mt-6 inline-flex h-11 items-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white"
                >
                    Browse Marketplace
                </Link>
            </div>

            <div
                v-else
                class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]"
            >
                <section class="space-y-4">
                    <article
                        v-for="item in cartItems"
                        :key="item.id"
                        class="grid gap-4 rounded-3xl border border-stone-200 bg-white p-4 shadow-sm sm:grid-cols-[150px_minmax(0,1fr)] dark:border-stone-800 dark:bg-stone-900"
                    >
                        <Link
                            :href="itemHref(item)"
                            class="overflow-hidden rounded-xl bg-stone-100 dark:bg-stone-800"
                        >
                            <img
                                v-if="itemPreview(item)"
                                :src="itemPreview(item)!"
                                :alt="itemTitle(item)"
                                class="aspect-[4/3] h-full w-full object-cover"
                            />
                        </Link>

                        <div
                            class="flex min-w-0 flex-col justify-between gap-4"
                        >
                            <div>
                                <Link
                                    :href="itemHref(item)"
                                    class="text-lg font-semibold transition hover:text-[var(--brand-accent)]"
                                >
                                    {{ itemTitle(item) }}
                                </Link>

                                <p class="mt-1 text-sm font-medium">
                                    {{ packageName(item) }}
                                </p>

                                <div
                                    v-if="item.configuration?.labels?.length"
                                    class="mt-3 flex flex-wrap gap-2"
                                >
                                    <span
                                        v-for="label in item.configuration.labels"
                                        :key="label.group"
                                        class="rounded-full bg-stone-100 px-3 py-1 text-xs dark:bg-stone-800"
                                    >
                                        {{ label.group }}:
                                        {{ label.values.join(', ') }}
                                    </span>
                                </div>

                                <div v-if="item.shipping_address" class="mt-3 rounded-xl border border-stone-200 p-3 text-xs leading-5 text-stone-600 dark:border-stone-700 dark:text-stone-300">
                                    <p class="font-semibold text-stone-900 dark:text-white">Ships to {{ item.shipping_address.full_name }}</p>
                                    <p>{{ item.shipping_address.address_line_1 }}<template v-if="item.shipping_address.address_line_2">, {{ item.shipping_address.address_line_2 }}</template></p>
                                    <p>{{ item.shipping_address.city }}, {{ item.shipping_address.region }} {{ item.shipping_address.postal_code }} · {{ item.shipping_address.country_code }}</p>
                                </div>

                                <p
                                    v-if="item.pricing?.pricing_tier_label"
                                    class="mt-2 text-xs font-semibold text-emerald-600"
                                >
                                    Bulk price:
                                    {{ item.pricing.pricing_tier_label }}
                                </p>
                            </div>

                            <div
                                class="flex flex-wrap items-center justify-between gap-4"
                            >
                                <div
                                    v-if="item.kind === 'asset'"
                                    class="flex h-10 items-center rounded-xl border border-stone-300 dark:border-stone-700"
                                >
                                    <button
                                        type="button"
                                        class="h-full px-3"
                                        :disabled="item.quantity <= 1"
                                        :aria-label="`Decrease quantity for ${itemTitle(item)}`"
                                        @click="updateQuantity(item, item.quantity - 1)"
                                    >
                                        <Minus class="h-4 w-4" />
                                    </button>

                                    <span
                                        class="min-w-10 text-center text-sm font-semibold"
                                    >
                                        {{ item.quantity }}
                                    </span>

                                    <button
                                        type="button"
                                        class="h-full px-3"
                                        :aria-label="`Increase quantity for ${itemTitle(item)}`"
                                        @click="updateQuantity(item, item.quantity + 1)"
                                    >
                                        <Plus class="h-4 w-4" />
                                    </button>
                                </div>

                                <span
                                    v-else
                                    class="text-sm text-stone-500"
                                >
                                    Quantity 1
                                </span>

                                <div class="ml-auto text-right">
                                    <p class="text-xs text-stone-500">
                                        {{
                                            formatPrice(
                                                item.unit_price_cents,
                                                item.currency,
                                            )
                                        }}
                                        each
                                    </p>

                                    <p class="text-lg font-semibold">
                                        {{
                                            formatPrice(
                                                item.line_total_cents,
                                                item.currency,
                                            )
                                        }}
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="h-10 w-10 rounded-full border border-stone-300 text-stone-500 transition hover:border-red-300 hover:text-red-600 dark:border-stone-700"
                                    :aria-label="`Remove ${itemTitle(item)}`"
                                    @click="removeItem(item)"
                                >
                                    <Trash2 class="mx-auto h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </article>
                </section>

                <aside class="lg:sticky lg:top-24 lg:self-start">
                    <div
                        class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900"
                    >
                        <h2 class="text-xl font-semibold">
                            Order Summary
                        </h2>

                        <div
                            class="mt-5 flex justify-between border-b border-stone-200 pb-4 text-sm dark:border-stone-800"
                        >
                            <span class="text-stone-500">
                                {{ totalQuantity() }}
                                {{ totalQuantity() === 1 ? 'item' : 'items' }}
                            </span>

                            <span class="font-medium">
                                {{ formatPrice(cartTotalCents) }}
                            </span>
                        </div>

                        <div class="mt-4 flex justify-between">
                            <span class="font-semibold">
                                Total
                            </span>

                            <span class="text-2xl font-semibold">
                                {{ formatPrice(cartTotalCents) }}
                            </span>
                        </div>

                        <form
                            method="POST"
                            action="/cart/checkout"
                            class="mt-6"
                        >
                            <input
                                type="hidden"
                                name="_token"
                                :value="csrfToken"
                            />

                            <Button
                                type="submit"
                                class="w-full"
                                :disabled="!cartItems.length"
                            >
                                Continue to Checkout
                            </Button>
                        </form>

                        <p
                            class="mt-4 flex gap-2 text-xs leading-5 text-stone-500"
                        >
                            <ShieldCheck
                                class="h-4 w-4 shrink-0"
                            />
                            Secure payment processing through Stripe.
                        </p>
                    </div>
                </aside>
            </div>
        </main>
    </PublicPageLayout>
</template>
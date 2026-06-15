<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Trash2, ShoppingCart } from '@lucide/vue';;
import { Button } from '@/components/ui/button';

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

function formatPrice(priceCents: number): string {
    return `$${(priceCents / 100).toFixed(2)}`;
}

function removeItem(cartItem: CartItem) {
    router.delete(`/cart/items/${cartItem.id}`, {
        preserveScroll: true,
    });
}

function clearCart() {
    router.delete('/cart', {
        preserveScroll: true,
    });
}

function checkout() {
    const form = document.createElement('form');

    form.method = 'POST';
    form.action = '/cart/checkout';

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

    document.body.appendChild(form);
    form.submit();
}

</script>

<template>
    <Head title="Shopping Cart" />

    <div class="space-y-8 p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold">Shopping Cart</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Review the images and licenses before checkout.
                </p>
            </div>

            <Button
                v-if="cartItems.length"
                variant="outline"
                class="gap-2"
                @click="clearCart"
            >
                <Trash2 class="h-4 w-4" />
                Clear Cart
            </Button>
        </div>

        <div
            v-if="!cartItems.length"
            class="rounded-lg border bg-card p-10 text-center shadow-sm"
        >
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border">
                <ShoppingCart class="h-6 w-6 text-muted-foreground" />
            </div>

            <h2 class="mt-4 text-xl font-semibold">Your cart is empty</h2>

            <p class="mt-2 text-sm text-muted-foreground">
                Add images to your cart before checking out.
            </p>

            <Button class="mt-6" as-child>
                <Link href="/images">
                    Browse Images
                </Link>
            </Button>
        </div>

        <div v-else class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div
                    v-for="cartItem in cartItems"
                    :key="cartItem.id"
                    class="flex flex-col gap-4 rounded-lg border bg-card p-4 shadow-sm sm:flex-row"
                >
                    <Link
                        :href="`/images/${cartItem.image.slug}`"
                        class="block h-32 w-full overflow-hidden rounded-md bg-muted sm:w-40"
                    >
                        <img
                            v-if="cartItem.image.preview_url"
                            :src="cartItem.image.preview_url"
                            :alt="cartItem.image.title"
                            class="h-full w-full object-cover"
                        />

                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center text-sm text-muted-foreground"
                        >
                            No preview
                        </div>
                    </Link>

                    <div class="flex flex-1 flex-col justify-between gap-4">
                        <div>
                            <Link
                                :href="`/images/${cartItem.image.slug}`"
                                class="text-lg font-semibold hover:underline"
                            >
                                {{ cartItem.image.title }}
                            </Link>

                            <div class="mt-2 text-sm text-muted-foreground">
                                License: {{ cartItem.license_type.name }}
                            </div>

                            <p
                                v-if="cartItem.license_type.description"
                                class="mt-2 line-clamp-2 text-sm text-muted-foreground"
                            >
                                {{ cartItem.license_type.description }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <div class="text-lg font-semibold">
                                {{ formatPrice(cartItem.price_cents) }}
                            </div>

                            <Button
                                variant="outline"
                                size="sm"
                                class="gap-2"
                                @click="removeItem(cartItem)"
                            >
                                <Trash2 class="h-4 w-4" />
                                Remove
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border bg-card p-6 shadow-sm">
                <h2 class="text-xl font-semibold">Order Summary</h2>

                <div class="mt-6 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Items</span>
                        <span>{{ cartItems.length }}</span>
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Subtotal</span>
                        <span>{{ formatPrice(cartTotalCents) }}</span>
                    </div>

                    <div class="border-t pt-3">
                        <div class="flex justify-between text-lg font-semibold">
                            <span>Total</span>
                            <span>{{ formatPrice(cartTotalCents) }}</span>
                        </div>
                    </div>
                </div>

                <Button
                    class="mt-6 w-full"
                    @click="checkout"
                >
                    Checkout
                </Button>

                <Button class="mt-3 w-full" variant="outline" as-child>
                    <Link href="/images">
                        Continue Shopping
                    </Link>
                </Button>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ShoppingCart } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';

const page = usePage();

const cart = computed(() => (page.props as any).cart ?? {
    count: 0,
    items: [],
});

function formatPrice(priceCents: number): string {
    return `$${(priceCents / 100).toFixed(2)}`;
}
</script>

<template>
    <div class="relative group">
        <div class="pb-3">
            <Button variant="ghost" size="sm" class="relative gap-2" as-child>
                <Link href="/cart">
                    <ShoppingCart class="h-5 w-5" />

                    <span
                        v-if="cart.count > 0"
                        class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1 text-xs text-primary-foreground"
                    >
                        {{ cart.count }}
                    </span>
                </Link>
            </Button>
        </div>

        <div
            class="absolute right-0 top-full z-50 hidden w-80 rounded-lg border bg-popover p-4 shadow-lg group-hover:block"
        >
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-semibold">Cart</h3>

                <Link href="/cart" class="text-sm text-muted-foreground hover:underline">
                    View Cart
                </Link>
            </div>

            <div v-if="!cart.items.length" class="py-6 text-center text-sm text-muted-foreground">
                Your cart is empty.
            </div>

            <div v-else class="space-y-3">
                <Link
                    v-for="item in cart.items"
                    :key="item.id"
                    :href="`/images/${item.image.slug}`"
                    class="flex gap-3 rounded-md p-2 hover:bg-muted"
                >
                    <div class="h-14 w-14 overflow-hidden rounded bg-muted">
                        <img
                            v-if="item.image.thumbnail_url || item.image.icon_url"
                            :src="item.image.thumbnail_url || item.image.icon_url"
                            :alt="item.image.title"
                            class="h-full w-full object-cover"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium">
                            {{ item.image.title }}
                        </div>

                        <div class="truncate text-xs text-muted-foreground">
                            {{ item.license_type.name }}
                        </div>

                        <div class="text-xs font-semibold">
                            {{ formatPrice(item.price_cents) }}
                        </div>
                    </div>
                </Link>

                <Button class="w-full" as-child>
                    <Link href="/cart">
                        Checkout
                    </Link>
                </Button>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, ShoppingCart, X } from '@lucide/vue';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
} from 'vue';

type CartItem = {
    id: number;
    price_cents: number;
    image?: {
        title?: string | null;
        slug?: string | null;
        thumbnail_url?: string | null;
        icon_url?: string | null;
    } | null;
    license_type?: {
        name?: string | null;
    } | null;
};

type SharedCart = {
    count: number;
    items: CartItem[];
};

const props = withDefaults(
    defineProps<{
        compact?: boolean;
    }>(),
    {
        compact: false,
    },
);

const page = usePage();

const rootElement = ref<HTMLElement | null>(null);
const triggerElement = ref<HTMLElement | null>(null);
const panelElement = ref<HTMLElement | null>(null);

const hoverOpen = ref(false);
const pinnedOpen = ref(false);

const isOpen = computed(() =>
    !props.compact
    && (hoverOpen.value || pinnedOpen.value),
);

const cart = computed<SharedCart>(() => {
    const shared = (page.props.cart ?? {}) as Partial<SharedCart>;

    return {
        count: Number(shared.count ?? 0),
        items: Array.isArray(shared.items)
            ? shared.items
            : [],
    };
});

const subtotalCents = computed(() =>
    cart.value.items.reduce(
        (total, item) => total + Number(item.price_cents ?? 0),
        0,
    ),
);

function formatPrice(priceCents: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(priceCents / 100);
}

function handleMouseEnter(): void {
    if (!props.compact) {
        hoverOpen.value = true;
    }
}

function handleMouseLeave(): void {
    hoverOpen.value = false;
}

async function togglePinned(): Promise<void> {
    if (props.compact) {
        return;
    }

    pinnedOpen.value = !pinnedOpen.value;

    if (pinnedOpen.value) {
        hoverOpen.value = true;
        await nextTick();

        panelElement.value
            ?.querySelector<HTMLElement>('a[href="/cart"]')
            ?.focus();
    }
}

function closeMenu({
    restoreFocus = false,
}: {
    restoreFocus?: boolean;
} = {}): void {
    hoverOpen.value = false;
    pinnedOpen.value = false;

    if (restoreFocus) {
        triggerElement.value?.focus();
    }
}

function handleDocumentPointerDown(event: PointerEvent): void {
    if (!isOpen.value || !rootElement.value) {
        return;
    }

    const target = event.target as Node | null;

    if (target && !rootElement.value.contains(target)) {
        closeMenu();
    }
}

function handleDocumentKeydown(event: KeyboardEvent): void {
    if (event.key !== 'Escape' || !isOpen.value) {
        return;
    }

    event.preventDefault();

    closeMenu({
        restoreFocus: true,
    });
}

function handleFocusOut(event: FocusEvent): void {
    if (pinnedOpen.value) {
        return;
    }

    const nextTarget = event.relatedTarget as Node | null;

    if (
        nextTarget
        && rootElement.value?.contains(nextTarget)
    ) {
        return;
    }

    hoverOpen.value = false;
}

onMounted(() => {
    document.addEventListener('pointerdown', handleDocumentPointerDown);
    document.addEventListener('keydown', handleDocumentKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', handleDocumentPointerDown);
    document.removeEventListener('keydown', handleDocumentKeydown);
});
</script>

<template>
    <div
        ref="rootElement"
        class="relative"
        @mouseenter="handleMouseEnter"
        @mouseleave="handleMouseLeave"
        @focusout="handleFocusOut"
    >
        <Link
            v-if="compact"
            href="/cart"
            class="relative inline-flex h-11 w-11 items-center justify-center rounded-full border border-stone-300 transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] dark:border-stone-700"
            aria-label="View shopping cart"
        >
            <ShoppingCart class="h-5 w-5" />

            <span
                v-if="cart.count > 0"
                class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-[var(--brand-accent)] px-1 text-[10px] font-bold leading-none text-white"
                :aria-label="`${cart.count} items in cart`"
            >
                {{ cart.count > 99 ? '99+' : cart.count }}
            </span>
        </Link>

        <button
            v-else
            ref="triggerElement"
            type="button"
            class="relative inline-flex h-10 items-center justify-center gap-2 rounded-full border border-stone-300 px-4 text-sm font-medium transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] dark:border-stone-700"
            aria-haspopup="dialog"
            :aria-expanded="isOpen"
            aria-controls="public-cart-preview"
            @click="togglePinned"
            @focus="handleMouseEnter"
        >
            <ShoppingCart class="h-5 w-5" />
            <span>Cart</span>

            <span
                v-if="cart.count > 0"
                class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-[var(--brand-accent)] px-1 text-[10px] font-bold leading-none text-white"
                :aria-label="`${cart.count} items in cart`"
            >
                {{ cart.count > 99 ? '99+' : cart.count }}
            </span>
        </button>

        <div
            v-if="isOpen"
            class="absolute right-0 top-full z-50 w-80 pt-3"
        >
            <div
                id="public-cart-preview"
                ref="panelElement"
                role="dialog"
                aria-label="Shopping cart preview"
                class="public-rise-enter overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-2xl dark:border-stone-800 dark:bg-stone-900"
            >
                <div class="flex items-center justify-between border-b border-stone-200 px-4 py-3 dark:border-stone-800">
                    <div>
                        <div class="font-semibold">Shopping Cart</div>
                        <div class="text-xs text-stone-500 dark:text-stone-400">
                            {{ cart.count }}
                            {{ cart.count === 1 ? 'item' : 'items' }}
                        </div>
                    </div>

                    <button
                        v-if="pinnedOpen"
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full text-stone-500 transition hover:bg-stone-100 dark:hover:bg-stone-800"
                        aria-label="Close cart preview"
                        @click="closeMenu({ restoreFocus: true })"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <ShoppingCart
                        v-else
                        class="h-5 w-5 text-stone-400"
                    />
                </div>

                <div
                    v-if="cart.items.length"
                    class="max-h-80 divide-y divide-stone-100 overflow-y-auto dark:divide-stone-800"
                >
                    <div
                        v-for="item in cart.items"
                        :key="item.id"
                        class="flex gap-3 px-4 py-3"
                    >
                        <img
                            v-if="item.image?.thumbnail_url || item.image?.icon_url"
                            :src="item.image.thumbnail_url ?? item.image.icon_url ?? ''"
                            :alt="item.image?.title ?? 'Cart image'"
                            class="h-14 w-14 shrink-0 rounded-lg object-cover"
                        />

                        <div
                            v-else
                            class="h-14 w-14 shrink-0 rounded-lg bg-stone-200 dark:bg-stone-800"
                        />

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium">
                                {{ item.image?.title ?? 'Image' }}
                            </div>

                            <div class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                                {{ item.license_type?.name ?? 'License' }}
                            </div>

                            <div class="mt-1 text-xs font-semibold">
                                {{ formatPrice(item.price_cents) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="px-4 py-8 text-center"
                >
                    <ShoppingCart class="mx-auto h-8 w-8 text-stone-300 dark:text-stone-700" />

                    <p class="mt-3 text-sm font-medium">
                        Your cart is empty
                    </p>

                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                        Add an image license to get started.
                    </p>
                </div>

                <div
                    v-if="cart.items.length"
                    class="flex items-center justify-between border-t border-stone-200 px-4 py-3 dark:border-stone-800"
                >
                    <span class="text-sm text-stone-500 dark:text-stone-400">
                        Subtotal
                    </span>

                    <span class="font-semibold">
                        {{ formatPrice(subtotalCents) }}
                    </span>
                </div>

                <div class="border-t border-stone-200 p-3 dark:border-stone-800">
                    <Link
                        href="/cart"
                        class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-full bg-[var(--brand-primary)] px-4 text-sm font-semibold text-white transition hover:opacity-90"
                        @click="closeMenu"
                    >
                        View Cart
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

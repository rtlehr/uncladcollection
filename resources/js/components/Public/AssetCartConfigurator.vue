<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Check,
    Minus,
    Plus,
    ShoppingCart,
    Sparkles,
    Trash2,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';

import type {
    PublicAssetConfigurationGroup,
    PublicAssetOffering,
    PublicAssetPricingTier,
} from '@/types/publicAsset';
import AssetConfigurationSelector from './AssetConfigurationSelector.vue';
import AssetFormatBadge from './AssetFormatBadge.vue';
import AssetFulfillmentBadge from './AssetFulfillmentBadge.vue';
import AssetOfferingCard from './AssetOfferingCard.vue';
import AssetShippingAddress from './AssetShippingAddress.vue';
import type {ShippingAddressPayload} from './AssetShippingAddress.vue';


type DraftLine = {
    id: number;
    quantity: number;
    selections: Record<string, unknown>;
    labels: Record<string, string>;
    adjustment_cents: number;
    configured_unit_price_cents: number;
};

type ConfigurationPayload = {
    selections: Record<string, unknown>;
    adjustment_cents: number;
    total_price_cents: number;
    valid: boolean;
    labels: Record<string, string>;
};

const props = defineProps<{
    groups: PublicAssetConfigurationGroup[];
    offerings: PublicAssetOffering[];
    assetTitle: string;
    allowQuantity: boolean;
    collectShippingAddress: boolean;
    shippingAddressRequired: boolean;
    fulfillmentType: 'digital' | 'physical' | 'hybrid';
}>();

const offeringId = ref<number | null>(props.offerings[0]?.id ?? null);
const quantity = ref(1);
const lines = ref<DraftLine[]>([]);
const processing = ref(false);
const shippingAddress = ref<ShippingAddressPayload | null>(null);
const shippingAddressValid = ref(!props.shippingAddressRequired);

const current = reactive({
    selections: {} as Record<string, unknown>,
    labels: {} as Record<string, string>,
    adjustment_cents: 0,
    total_price_cents: props.offerings[0]?.price_cents ?? 0,
    valid: props.groups.length === 0,
});

let nextId = 1;

const offering = computed(
    () => props.offerings.find((item) => item.id === offeringId.value) ?? null,
);

const currency = computed(() => offering.value?.currency ?? 'USD');

const totalQuantity = computed(() =>
    lines.value.reduce((sum, line) => sum + line.quantity, 0),
);

const needsConfigurationBuilder = computed(
    () => props.groups.length > 0 || props.allowQuantity,
);

const directPurchase = computed(
    () => props.groups.length === 0 && !props.allowQuantity,
);

const displayedQuantity = computed(() => {
    if (lines.value.length > 0) {
        return totalQuantity.value;
    }

    if (directPurchase.value) {
        return 1;
    }

    if (props.groups.length > 0 && current.valid) {
        return props.allowQuantity ? quantity.value : 1;
    }

    return 0;
});

const canAddConfiguration = computed(() => {
    if (processing.value || !offering.value) {
        return false;
    }

    if (props.groups.length > 0 && !current.valid) {
        return false;
    }

    return !props.allowQuantity || (quantity.value >= 1 && quantity.value <= 999);
});

const canSubmit = computed(() => {
    if (!offering.value || processing.value || !shippingAddressValid.value) {
        return false;
    }

    if (lines.value.length > 0 || directPurchase.value) {
        return true;
    }

    return (
        props.groups.length > 0
        && current.valid
        && (!props.allowQuantity || (quantity.value >= 1 && quantity.value <= 999))
    );
});


function snapshotRecord(
    record: Record<string, unknown>,
): Record<string, unknown> {
    return Object.fromEntries(
        Object.entries(record).map(([key, value]) => [
            key,
            Array.isArray(value) ? [...value] : value,
        ]),
    );
}

function snapshotLabels(
    labels: Record<string, string>,
): Record<string, string> {
    return { ...labels };
}

function normalizedSelections(selections: Record<string, unknown>): string {
    return JSON.stringify(
        Object.keys(selections)
            .sort()
            .reduce<Record<string, unknown>>((result, key) => {
                const value = selections[key];

                result[key] = Array.isArray(value)
                    ? [...value].sort()
                    : value;

                return result;
            }, {}),
    );
}

function applies(
    tier: PublicAssetPricingTier,
    aggregateQuantity: number,
): boolean {
    return (
        aggregateQuantity >= tier.minimum_quantity
        && (
            tier.maximum_quantity === null
            || aggregateQuantity <= tier.maximum_quantity
        )
    );
}

function finalUnitPrice(
    configuredUnit: number,
    aggregateQuantity: number,
): number {
    const tier = offering.value?.pricing_tiers.find((candidate) =>
        applies(candidate, aggregateQuantity),
    );

    if (!tier) {
        return configuredUnit;
    }

    if (
        tier.pricing_type === 'fixed_unit_price'
        && tier.unit_price_cents !== null
    ) {
        return Math.min(configuredUnit, tier.unit_price_cents);
    }

    if (
        tier.pricing_type === 'percentage_off'
        && tier.percentage_off !== null
    ) {
        return Math.max(
            0,
            Math.round(
                configuredUnit
                * (1 - Number(tier.percentage_off) / 100),
            ),
        );
    }

    return configuredUnit;
}

const pricedLines = computed(() =>
    lines.value.map((line) => {
        const finalUnit = finalUnitPrice(
            line.configured_unit_price_cents,
            totalQuantity.value,
        );

        return {
            ...line,
            final_unit_price_cents: finalUnit,
            line_total_cents: finalUnit * line.quantity,
            savings_cents:
                Math.max(
                    0,
                    line.configured_unit_price_cents - finalUnit,
                ) * line.quantity,
        };
    }),
);

const subtotal = computed(() =>
    pricedLines.value.reduce(
        (sum, line) => sum + line.line_total_cents,
        0,
    ),
);

const totalSavings = computed(() =>
    pricedLines.value.reduce(
        (sum, line) => sum + line.savings_cents,
        0,
    ),
);

const activeTier = computed(
    () =>
        offering.value?.pricing_tiers.find((tier) =>
            applies(tier, totalQuantity.value),
        ) ?? null,
);

const nextTier = computed(
    () =>
        offering.value?.pricing_tiers.find(
            (tier) => tier.minimum_quantity > totalQuantity.value,
        ) ?? null,
);

const unitsUntilNextTier = computed(() =>
    nextTier.value
        ? Math.max(
            0,
            nextTier.value.minimum_quantity - totalQuantity.value,
        )
        : null,
);

function formatPrice(cents: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency.value,
    }).format(cents / 100);
}

function receiveConfiguration(payload: ConfigurationPayload): void {
    current.selections = payload.selections;
    current.adjustment_cents = payload.adjustment_cents;
    current.total_price_cents = payload.total_price_cents;
    current.valid = payload.valid;
    current.labels = payload.labels;
}

function selectOffering(id: number): void {
    if (offeringId.value === id) {
        return;
    }

    offeringId.value = id;
    lines.value = [];
    quantity.value = 1;
}

function addConfiguration(): void {
    if (!canAddConfiguration.value || !offering.value) {
        return;
    }

    const lineQuantity = props.allowQuantity ? quantity.value : 1;
    const hash = normalizedSelections(current.selections);

    const existing = lines.value.find(
        (line) => normalizedSelections(line.selections) === hash,
    );

    if (existing) {
        existing.quantity += lineQuantity;
    } else {
        lines.value.push({
            id: nextId++,
            quantity: lineQuantity,
            selections: snapshotRecord(current.selections),
            labels: snapshotLabels(current.labels),
            adjustment_cents: current.adjustment_cents,
            configured_unit_price_cents: current.total_price_cents,
        });
    }

    quantity.value = 1;
}

function changeLineQuantity(
    lineId: number,
    difference: number,
): void {
    if (!props.allowQuantity) {
        return;
    }

    const line = lines.value.find(
        (candidate) => candidate.id === lineId,
    );

    if (!line) {
        return;
    }

    line.quantity = Math.min(
        999,
        Math.max(1, line.quantity + difference),
    );
}

function removeLine(id: number): void {
    lines.value = lines.value.filter((line) => line.id !== id);
}

function receiveShippingAddress(payload: { address: ShippingAddressPayload; valid: boolean }): void {
    shippingAddress.value = payload.address;
    shippingAddressValid.value = payload.valid;
}

function submit(): void {
    if (!canSubmit.value || !offering.value) {
        return;
    }

    const submittedLines = lines.value.length > 0
        ? lines.value.map((line) => ({
            quantity: line.quantity,
            selections: line.selections,
        }))
        : directPurchase.value
            ? [
                {
                    quantity: 1,
                    selections: {},
                },
            ]
            : props.groups.length > 0 && current.valid
                ? [
                    {
                        quantity: props.allowQuantity
                            ? quantity.value
                            : 1,
                        selections: snapshotRecord(current.selections),
                    },
                ]
                : [];

    if (submittedLines.length === 0) {
        return;
    }

    processing.value = true;

    router.post(
        '/cart/items',
        {
            asset_offering_id: offering.value.id,
            lines: submittedLines,
            shipping_address: props.collectShippingAddress ? shippingAddress.value : null,
        } as any,
        {
            preserveScroll: true,
            onSuccess: () => {
                lines.value = [];
                quantity.value = 1;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

watch(offering, (selected) => {
    current.total_price_cents = selected?.price_cents ?? 0;
});

</script>


<template>
    <section
        v-if="offerings.length"
        id="purchase"
        class="scroll-mt-24"
    >
        <div
            class="overflow-hidden rounded-[2rem] border border-stone-200 bg-stone-50/70 shadow-sm dark:border-stone-800 dark:bg-stone-950/40"
        >
            <header
                class="border-b border-stone-200 bg-white px-6 py-7 sm:px-8 dark:border-stone-800 dark:bg-stone-900"
            >
                <div
                    class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="max-w-3xl">
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]"
                        >
                            Complete your purchase
                        </p>

                        <h2
                            class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl"
                        >
                            Build your order for {{ assetTitle }}
                        </h2>

                        <p
                            class="mt-3 text-sm leading-6 text-stone-600 dark:text-stone-300"
                        >
                            Choose a package, complete any product options,
                            and review the final purchase before adding it to
                            your cart.
                        </p>
                    </div>

                    <AssetFulfillmentBadge
                        :type="fulfillmentType"
                        size="md"
                    />
                </div>
            </header>

            <div
                class="grid gap-0 xl:grid-cols-[minmax(0,1fr)_360px]"
            >
                <div
                    class="space-y-8 p-5 sm:p-7 lg:p-8 xl:border-r xl:border-stone-200 dark:xl:border-stone-800"
                >
                    <section>
                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                        >
                            <div>
                                <p
                                    class="flex items-center gap-3 text-sm font-semibold"
                                >
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[var(--brand-primary)] text-xs text-white"
                                    >
                                        1
                                    </span>
                                    Available packages
                                </p>

                                <p
                                    class="mt-2 pl-10 text-sm text-stone-500"
                                >
                                    Select the license and file package that
                                    best matches your intended use.
                                </p>
                            </div>

                            <p class="pl-10 text-xs text-stone-500 sm:pl-0">
                                {{ offerings.length }}
                                option{{ offerings.length === 1 ? '' : 's' }}
                            </p>
                        </div>

                        <div
                            class="mt-5 grid gap-4 md:grid-cols-2"
                        >
                            <AssetOfferingCard
                                v-for="(item, index) in offerings"
                                :key="item.id"
                                :offering="item"
                                :selected="item.id === offeringId"
                                :featured="
                                    index === 1
                                    && offerings.length > 2
                                "
                                @select="selectOffering"
                            />
                        </div>
                    </section>

                    <section v-if="needsConfigurationBuilder">
                        <div>
                            <p
                                class="flex items-center gap-3 text-sm font-semibold"
                            >
                                <span
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[var(--brand-primary)] text-xs text-white"
                                >
                                    2
                                </span>
                                Configure your product
                            </p>

                            <p
                                class="mt-2 pl-10 text-sm text-stone-500"
                            >
                                Complete mandatory options and add multiple
                                combinations when needed.
                            </p>
                        </div>

                        <div class="mt-5 space-y-5">
                            <AssetConfigurationSelector
                                v-if="groups.length && offering"
                                :key="offering.id"
                                :groups="groups"
                                :base-price-cents="
                                    offering.price_cents
                                "
                                :currency="offering.currency"
                                @configuration-change="
                                    receiveConfiguration
                                "
                            />

                            <section
                                v-if="
                                    !groups.length
                                    && allowQuantity
                                "
                                class="rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900"
                            >
                                <h3 class="font-semibold">
                                    Standard product
                                </h3>

                                <p
                                    class="mt-1 text-sm text-stone-500"
                                >
                                    No additional options are required.
                                    Select the quantity you need.
                                </p>
                            </section>

                            <section
                                class="rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900"
                            >
                                <div
                                    class="flex flex-col gap-4 sm:flex-row sm:items-end"
                                >
                                    <div v-if="allowQuantity">
                                        <label
                                            class="text-sm font-semibold"
                                        >
                                            Quantity
                                        </label>

                                        <div
                                            class="mt-2 flex h-11 items-center rounded-xl border border-stone-200 dark:border-stone-700"
                                        >
                                            <button
                                                type="button"
                                                class="h-full px-4"
                                                :disabled="
                                                    quantity <= 1
                                                "
                                                aria-label="Decrease quantity"
                                                @click="quantity--"
                                            >
                                                <Minus
                                                    class="h-4 w-4"
                                                />
                                            </button>

                                            <input
                                                v-model.number="
                                                    quantity
                                                "
                                                type="number"
                                                min="1"
                                                max="999"
                                                class="w-20 border-0 bg-transparent text-center font-semibold"
                                            />

                                            <button
                                                type="button"
                                                class="h-full px-4"
                                                :disabled="
                                                    quantity >= 999
                                                "
                                                aria-label="Increase quantity"
                                                @click="quantity++"
                                            >
                                                <Plus
                                                    class="h-4 w-4"
                                                />
                                            </button>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        class="h-11 flex-1 rounded-full border border-[var(--brand-primary)] px-5 text-sm font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)] hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="
                                            !canAddConfiguration
                                        "
                                        @click="addConfiguration"
                                    >
                                        {{
                                            groups.length
                                                ? 'Add this configuration'
                                                : 'Add quantity to order'
                                        }}
                                    </button>
                                </div>
                            </section>
                        </div>
                    </section>

                    <section v-if="collectShippingAddress">
                        <div>
                            <p
                                class="flex items-center gap-3 text-sm font-semibold"
                            >
                                <span
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[var(--brand-primary)] text-xs text-white"
                                >
                                    {{ needsConfigurationBuilder ? 3 : 2 }}
                                </span>
                                Shipping information
                            </p>

                            <p
                                class="mt-2 pl-10 text-sm text-stone-500"
                            >
                                {{
                                    shippingAddressRequired
                                        ? 'A complete shipping address is required for this product.'
                                        : 'Shipping information is optional for this product.'
                                }}
                            </p>
                        </div>

                        <div class="mt-5">
                            <AssetShippingAddress
                                :required="
                                    shippingAddressRequired
                                "
                                @change="
                                    receiveShippingAddress
                                "
                            />
                        </div>
                    </section>

                    <section
                        v-if="
                            offering
                            && offering.pricing_tiers.length
                        "
                        class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900/50 dark:bg-amber-950/20"
                    >
                        <div class="flex items-start gap-3">
                            <Sparkles
                                class="mt-0.5 h-5 w-5 shrink-0 text-amber-600"
                            />

                            <div class="w-full">
                                <h3 class="font-semibold">
                                    Buy more &amp; save
                                </h3>

                                <p
                                    class="mt-1 text-sm text-stone-600 dark:text-stone-300"
                                >
                                    Quantities across matching
                                    configurations count together.
                                </p>

                                <div
                                    class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3"
                                >
                                    <div
                                        v-for="
                                            tier in offering.pricing_tiers
                                        "
                                        :key="tier.id"
                                        class="rounded-xl border border-amber-200 bg-white/70 p-3 text-sm dark:border-amber-900/50 dark:bg-stone-900/50"
                                    >
                                        <p class="font-semibold">
                                            {{
                                                tier.minimum_quantity
                                            }}{{
                                                tier.maximum_quantity
                                                    ? `–${tier.maximum_quantity}`
                                                    : '+'
                                            }}
                                            items
                                        </p>

                                        <p
                                            class="mt-1 text-stone-500"
                                        >
                                            <template
                                                v-if="
                                                    tier.pricing_type
                                                        === 'fixed_unit_price'
                                                    && tier.unit_price_cents
                                                        !== null
                                                "
                                            >
                                                {{
                                                    formatPrice(
                                                        tier.unit_price_cents,
                                                    )
                                                }}
                                                each
                                            </template>

                                            <template v-else>
                                                {{
                                                    Number(
                                                        tier.percentage_off,
                                                    )
                                                }}% off
                                            </template>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <aside
                    v-if="offering"
                    class="bg-white p-5 sm:p-7 xl:sticky xl:top-20 xl:self-start dark:bg-stone-900"
                >
                    <section>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]"
                        >
                            Review your order
                        </p>

                        <div
                            class="mt-3 flex items-start justify-between gap-4"
                        >
                            <div>
                                <h3 class="text-xl font-semibold">
                                    {{ offering.name }}
                                </h3>

                                <p
                                    class="mt-1 text-sm text-stone-500"
                                >
                                    {{
                                        offering.license_type.name
                                    }}
                                </p>
                            </div>

                            <p class="text-xl font-bold">
                                {{
                                    formatPrice(
                                        offering.price_cents,
                                    )
                                }}
                            </p>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <AssetFormatBadge
                                v-for="format in [
                                    ...new Set(
                                        offering.files.map(
                                            (file) =>
                                                file.extension,
                                        ),
                                    ),
                                ]"
                                :key="format"
                                :format="format"
                            />
                        </div>

                        <div
                            v-if="pricedLines.length"
                            class="mt-6 space-y-3 border-t border-stone-200 pt-5 dark:border-stone-800"
                        >
                            <article
                                v-for="line in pricedLines"
                                :key="line.id"
                                class="rounded-xl border border-stone-200 p-4 dark:border-stone-700"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <div
                                            v-if="
                                                Object.values(
                                                    line.labels,
                                                ).filter(Boolean)
                                                    .length
                                            "
                                            class="space-y-1 text-sm"
                                        >
                                            <p
                                                v-for="(
                                                    value,
                                                    key
                                                ) in line.labels"
                                                v-show="value"
                                                :key="key"
                                            >
                                                <span
                                                    class="text-stone-500"
                                                >
                                                    {{ key }}:
                                                </span>

                                                <span
                                                    class="font-medium"
                                                >
                                                    {{ value }}
                                                </span>
                                            </p>
                                        </div>

                                        <p
                                            v-else
                                            class="text-sm font-medium"
                                        >
                                            Standard configuration
                                        </p>

                                        <div
                                            v-if="allowQuantity"
                                            class="mt-3 flex h-9 w-fit items-center rounded-lg border border-stone-200 dark:border-stone-700"
                                        >
                                            <button
                                                type="button"
                                                class="h-full px-2"
                                                aria-label="Decrease line quantity"
                                                @click="
                                                    changeLineQuantity(
                                                        line.id,
                                                        -1,
                                                    )
                                                "
                                            >
                                                <Minus
                                                    class="h-3.5 w-3.5"
                                                />
                                            </button>

                                            <span
                                                class="min-w-8 text-center text-sm font-semibold"
                                            >
                                                {{ line.quantity }}
                                            </span>

                                            <button
                                                type="button"
                                                class="h-full px-2"
                                                aria-label="Increase line quantity"
                                                @click="
                                                    changeLineQuantity(
                                                        line.id,
                                                        1,
                                                    )
                                                "
                                            >
                                                <Plus
                                                    class="h-3.5 w-3.5"
                                                />
                                            </button>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <p class="font-semibold">
                                            {{
                                                formatPrice(
                                                    line.line_total_cents,
                                                )
                                            }}
                                        </p>

                                        <p
                                            class="text-xs text-stone-500"
                                        >
                                            {{
                                                formatPrice(
                                                    line.final_unit_price_cents,
                                                )
                                            }}
                                            each
                                        </p>

                                        <button
                                            type="button"
                                            class="mt-3 inline-flex text-stone-400 transition hover:text-red-600"
                                            aria-label="Remove configuration"
                                            @click="
                                                removeLine(line.id)
                                            "
                                        >
                                            <Trash2
                                                class="h-4 w-4"
                                            />
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div
                            v-else-if="
                                needsConfigurationBuilder
                                && !current.valid
                            "
                            class="mt-6 rounded-xl border border-dashed border-stone-300 p-4 text-sm text-stone-500 dark:border-stone-700"
                        >
                            Complete the mandatory options to
                            finish your order.
                        </div>

                        <div
                            v-else-if="
                                needsConfigurationBuilder
                            "
                            class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-200"
                        >
                            Your current configuration is ready to
                            add to the cart.
                        </div>

                        <div
                            v-else
                            class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-200"
                        >
                            This package is ready to add. No
                            additional configuration is required.
                        </div>

                        <div
                            v-if="pricedLines.length"
                            class="mt-6 space-y-3 border-t border-stone-200 pt-5 text-sm dark:border-stone-800"
                        >
                            <div class="flex justify-between">
                                <span class="text-stone-500">
                                    Total items
                                </span>

                                <span class="font-medium">
                                    {{ totalQuantity }}
                                </span>
                            </div>

                            <div
                                v-if="totalSavings"
                                class="flex justify-between text-emerald-700 dark:text-emerald-400"
                            >
                                <span>Quantity savings</span>

                                <span class="font-medium">
                                    −{{
                                        formatPrice(
                                            totalSavings,
                                        )
                                    }}
                                </span>
                            </div>

                            <div
                                v-if="activeTier"
                                class="flex items-center gap-2 rounded-xl bg-emerald-50 p-3 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300"
                            >
                                <Check class="h-4 w-4" />
                                Quantity pricing unlocked
                            </div>

                            <div
                                v-else-if="
                                    unitsUntilNextTier
                                "
                                class="rounded-xl bg-amber-50 p-3 text-amber-800 dark:bg-amber-950/30 dark:text-amber-200"
                            >
                                Add {{ unitsUntilNextTier }} more
                                item{{
                                    unitsUntilNextTier === 1
                                        ? ''
                                        : 's'
                                }}
                                to unlock the next price.
                            </div>
                        </div>

                        <div
                            class="mt-6 flex items-end justify-between border-t border-stone-200 pt-5 dark:border-stone-800"
                        >
                            <span class="font-semibold">
                                Subtotal
                            </span>

                            <span class="text-3xl font-bold">
                                {{
                                    formatPrice(
                                        pricedLines.length
                                            ? subtotal
                                            : current.valid
                                                ? current.total_price_cents
                                                : offering.price_cents,
                                    )
                                }}
                            </span>
                        </div>

                        <div
                            v-if="!canSubmit"
                            class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-950/30 dark:text-amber-200"
                        >
                            Complete all mandatory product
                            options{{
                                shippingAddressRequired
                                    ? ' and shipping fields'
                                    : ''
                            }}
                            to enable Add to Cart.
                        </div>

                        <button
                            type="button"
                            class="mt-5 inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-[var(--brand-primary)] px-6 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="!canSubmit"
                            @click="submit"
                        >
                            <ShoppingCart class="h-4 w-4" />

                            {{
                                processing
                                    ? 'Adding to cart…'
                                    : lines.length > 0
                                        ? `Add ${displayedQuantity} to Cart`
                                        : 'Add to Cart'
                            }}
                        </button>

                        <p
                            class="mt-3 text-center text-xs leading-5 text-stone-500"
                        >
                            Final totals are confirmed again before
                            Stripe checkout.
                        </p>
                    </section>
                </aside>
            </div>
        </div>
    </section>

    <section
        v-else
        class="rounded-3xl border border-dashed border-stone-300 p-8 text-center text-stone-500 dark:border-stone-700"
    >
        License offerings are being prepared for this asset.
    </section>
</template>

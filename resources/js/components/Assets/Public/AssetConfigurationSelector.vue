<script setup lang="ts">
import { Check } from '@lucide/vue';
import { computed, reactive, watch } from 'vue';

import type { PublicAssetConfigurationGroup } from '@/types/publicAsset';

type ConfigurationPayload = {
    selections: Record<string, unknown>;
    adjustment_cents: number;
    total_price_cents: number;
    valid: boolean;
    labels: Record<string, string>;
};

const props = defineProps<{
    groups: PublicAssetConfigurationGroup[];
    basePriceCents: number;
    currency?: string;
}>();

const emit = defineEmits<{
    'configuration-change': [payload: ConfigurationPayload];
}>();

const selections = reactive<Record<string, unknown>>({});

function initialValue(group: PublicAssetConfigurationGroup): unknown {
    if (group.display_type === 'checkbox') {
        return group.values
            .filter((value) => value.is_default)
            .map((value) => value.value);
    }

    if (
        group.display_type === 'text'
        || group.display_type === 'number'
    ) {
        return '';
    }

    return (
        group.values.find((value) => value.is_default)?.value
        ?? ''
    );
}

function initializeSelections(): void {
    for (const group of props.groups) {
        selections[group.code] = initialValue(group);
    }
}

function snapshotSelections(): Record<string, unknown> {
    return Object.fromEntries(
        Object.entries(selections).map(([key, value]) => [
            key,
            Array.isArray(value) ? [...value] : value,
        ]),
    );
}

function hasValue(value: unknown): boolean {
    if (Array.isArray(value)) {
        return value.length > 0;
    }

    if (typeof value === 'string') {
        return value.trim() !== '';
    }

    return value !== null && value !== undefined;
}

function groupIsValid(
    group: PublicAssetConfigurationGroup,
): boolean {
    const value = selections[group.code];

    if (group.is_required && !hasValue(value)) {
        return false;
    }

    if (!hasValue(value)) {
        return true;
    }

    if (group.display_type !== 'number') {
        return true;
    }

    const numericValue = Number(value);

    if (!Number.isFinite(numericValue)) {
        return false;
    }

    if (
        group.minimum_value !== null
        && numericValue < group.minimum_value
    ) {
        return false;
    }

    if (
        group.maximum_value !== null
        && numericValue > group.maximum_value
    ) {
        return false;
    }

    return true;
}

initializeSelections();

const adjustment = computed(() =>
    props.groups.reduce((total, group) => {
        const selected = Array.isArray(selections[group.code])
            ? selections[group.code] as unknown[]
            : [selections[group.code]];

        return total + group.values
            .filter((value) => selected.includes(value.value))
            .reduce(
                (sum, value) =>
                    sum + value.price_adjustment_cents,
                0,
            );
    }, 0),
);

const total = computed(() =>
    Math.max(0, props.basePriceCents + adjustment.value),
);

const valid = computed(() =>
    props.groups.every(groupIsValid),
);

const labels = computed<Record<string, string>>(() =>
    Object.fromEntries(
        props.groups.map((group) => {
            const selected = selections[group.code];

            if (Array.isArray(selected)) {
                const selectedLabels = group.values
                    .filter((value) =>
                        selected.includes(value.value),
                    )
                    .map((value) => value.label);

                return [
                    group.name,
                    selectedLabels.join(', '),
                ];
            }

            const matched = group.values.find(
                (value) => value.value === selected,
            );

            return [
                group.name,
                matched?.label
                    ?? String(selected ?? '').trim(),
            ];
        }),
    ),
);

function price(cents: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.currency ?? 'USD',
    }).format(cents / 100);
}

watch(
    () => JSON.stringify(snapshotSelections()),
    () => {
        emit('configuration-change', {
            selections: snapshotSelections(),
            adjustment_cents: adjustment.value,
            total_price_cents: total.value,
            valid: valid.value,
            labels: { ...labels.value },
        });
    },
    {
        immediate: true,
    },
);
</script>

<template>
    <section
        v-if="groups.length"
        class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900"
    >
        <p
            class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-accent)]"
        >
            Configure your purchase
        </p>

        <h2 class="mt-2 text-xl font-semibold">
            Choose your options
        </h2>

        <p class="mt-2 text-sm text-stone-500">
            Fields marked Mandatory must be completed before this
            asset can be added to your cart.
        </p>

        <div class="mt-6 space-y-6">
            <fieldset
                v-for="group in groups"
                :key="group.id"
                class="space-y-3"
            >
                <legend class="font-semibold">
                    {{ group.name }}

                    <span
                        v-if="group.is_required"
                        class="ml-1 text-xs font-semibold text-red-600"
                    >
                        Mandatory
                    </span>

                    <span
                        v-else
                        class="ml-1 text-xs font-normal text-stone-400"
                    >
                        Optional
                    </span>
                </legend>

                <p
                    v-if="group.help_text"
                    class="text-sm text-stone-500"
                >
                    {{ group.help_text }}
                </p>

                <select
                    v-if="
                        ['select', 'dropdown'].includes(
                            group.display_type,
                        )
                    "
                    v-model="selections[group.code]"
                    class="h-11 w-full rounded-xl border bg-transparent px-3"
                >
                    <option value="">
                        {{
                            group.placeholder
                                || 'Choose an option'
                        }}
                    </option>

                    <option
                        v-for="value in group.values"
                        :key="value.id"
                        :value="value.value"
                    >
                        {{ value.label
                        }}{{
                            value.price_adjustment_cents
                                ? ` (${value.price_adjustment_cents > 0 ? '+' : ''}${price(value.price_adjustment_cents)})`
                                : ''
                        }}
                    </option>
                </select>

                <div
                    v-else-if="group.display_type === 'radio'"
                    class="grid gap-2 sm:grid-cols-2"
                >
                    <label
                        v-for="value in group.values"
                        :key="value.id"
                        :class="[
                            'flex cursor-pointer items-center justify-between rounded-xl border p-3 transition',
                            selections[group.code] === value.value
                                ? 'border-[var(--brand-primary)] bg-[var(--brand-primary)]/5 ring-1 ring-[var(--brand-primary)]/20'
                                : 'border-stone-200 hover:border-stone-400 dark:border-stone-700',
                        ]"
                    >
                        <span class="flex items-center gap-2">
                            <input
                                v-model="selections[group.code]"
                                type="radio"
                                :value="value.value"
                                class="sr-only"
                            />

                            <Check
                                v-if="
                                    selections[group.code]
                                        === value.value
                                "
                                class="h-4 w-4 text-[var(--brand-primary)]"
                            />

                            {{ value.label }}
                        </span>

                        <span
                            v-if="value.price_adjustment_cents"
                            class="text-sm text-stone-500"
                        >
                            {{
                                value.price_adjustment_cents > 0
                                    ? '+'
                                    : ''
                            }}{{
                                price(
                                    value.price_adjustment_cents,
                                )
                            }}
                        </span>
                    </label>
                </div>

                <div
                    v-else-if="group.display_type === 'checkbox'"
                    class="grid gap-2 sm:grid-cols-2"
                >
                    <label
                        v-for="value in group.values"
                        :key="value.id"
                        class="flex cursor-pointer items-center justify-between rounded-xl border border-stone-200 p-3 transition hover:border-stone-400 dark:border-stone-700"
                    >
                        <span class="flex items-center gap-2">
                            <input
                                v-model="selections[group.code]"
                                type="checkbox"
                                :value="value.value"
                            />

                            {{ value.label }}
                        </span>

                        <span
                            v-if="value.price_adjustment_cents"
                            class="text-sm text-stone-500"
                        >
                            {{
                                value.price_adjustment_cents > 0
                                    ? '+'
                                    : ''
                            }}{{
                                price(
                                    value.price_adjustment_cents,
                                )
                            }}
                        </span>
                    </label>
                </div>

                <div
                    v-else-if="
                        group.display_type === 'color_swatch'
                        || group.display_type === 'image_swatch'
                    "
                    class="flex flex-wrap gap-3"
                >
                    <label
                        v-for="value in group.values"
                        :key="value.id"
                        class="cursor-pointer"
                    >
                        <input
                            v-model="selections[group.code]"
                            type="radio"
                            :value="value.value"
                            class="peer sr-only"
                        />

                        <span
                            class="flex min-w-24 items-center gap-2 rounded-xl border p-2 transition peer-checked:border-[var(--brand-primary)] peer-checked:ring-2 peer-checked:ring-[var(--brand-primary)]/20"
                        >
                            <img
                                v-if="
                                    group.display_type
                                        === 'image_swatch'
                                    && value.image_url
                                "
                                :src="value.image_url"
                                :alt="value.label"
                                class="h-8 w-8 rounded-md object-cover"
                            />

                            <span
                                v-else
                                class="h-7 w-7 rounded-full border"
                                :style="{
                                    backgroundColor:
                                        value.swatch_color
                                        || '#ddd',
                                }"
                            />

                            {{ value.label }}
                        </span>
                    </label>
                </div>

                <input
                    v-else-if="group.display_type === 'number'"
                    v-model="selections[group.code]"
                    type="number"
                    :min="group.minimum_value ?? undefined"
                    :max="group.maximum_value ?? undefined"
                    :step="group.step_value ?? 1"
                    :placeholder="group.placeholder || undefined"
                    class="h-11 w-full rounded-xl border bg-transparent px-3"
                />

                <input
                    v-else
                    v-model="selections[group.code]"
                    type="text"
                    :placeholder="group.placeholder || undefined"
                    class="h-11 w-full rounded-xl border bg-transparent px-3"
                />
            </fieldset>
        </div>

        <div class="mt-6 flex items-end justify-between border-t pt-5">
            <div>
                <p class="text-sm text-stone-500">
                    Configured unit price
                </p>

                <p
                    v-if="adjustment"
                    class="text-xs text-stone-500"
                >
                    Includes
                    {{ adjustment > 0 ? '+' : ''
                    }}{{ price(adjustment) }} in options
                </p>

                <p
                    v-if="!valid"
                    class="mt-1 text-xs font-medium text-red-600"
                >
                    Complete all mandatory options to continue.
                </p>
            </div>

            <p class="text-2xl font-semibold">
                {{ price(total) }}
            </p>
        </div>
    </section>
</template>

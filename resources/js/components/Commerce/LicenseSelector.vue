<script setup lang="ts">
import {
    Check,
    Download,
    Image as ImageIcon,
} from '@lucide/vue';
import { computed } from 'vue';

import type { GalleryLicenseType } from '@/types/gallery';

const model = defineModel<number | null>({ required: true });

const props = defineProps<{
    licenses: GalleryLicenseType[];
}>();

const selectedLicense = computed(() =>
    props.licenses.find((license) => license.id === model.value) ?? null,
);

function formatPrice(
    priceCents: number,
    currency: string,
): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
    }).format(priceCents / 100);
}

function resolutionLabel(value: string | null): string {
    const labels: Record<string, string> = {
        original: 'Original Resolution',
        high_res: 'High Resolution',
        thumbnail: 'Thumbnail Resolution',
        icon: 'Icon Resolution',
    };

    return value
        ? labels[value] ?? value.replaceAll('_', ' ')
        : 'Standard Resolution';
}

function downloadsLabel(limit: number | null): string {
    return limit === null
        ? 'Unlimited downloads'
        : `${limit} download${limit === 1 ? '' : 's'}`;
}

function expirationLabel(days: number | null): string {
    return days === null
        ? 'License does not expire'
        : `Expires ${days} day${days === 1 ? '' : 's'} after purchase`;
}
</script>

<template>
    <fieldset>
        <legend class="sr-only">
            Choose a license
        </legend>

        <div class="space-y-3">
            <label
                v-for="license in licenses"
                :key="license.id"
                :class="[
                    'relative block cursor-pointer rounded-2xl border p-4 transition',
                    model === license.id
                        ? 'border-[var(--brand-accent)] bg-[color-mix(in_srgb,var(--brand-accent)_8%,transparent)] shadow-sm'
                        : 'border-stone-200 hover:border-stone-400 dark:border-stone-800 dark:hover:border-stone-600',
                ]"
            >
                <input
                    v-model="model"
                    type="radio"
                    name="license_type_id"
                    :value="license.id"
                    class="sr-only"
                />

                <div class="flex items-start gap-3">
                    <span
                        :class="[
                            'mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border',
                            model === license.id
                                ? 'border-[var(--brand-accent)] bg-[var(--brand-accent)] text-white'
                                : 'border-stone-400 dark:border-stone-600',
                        ]"
                        aria-hidden="true"
                    >
                        <Check
                            v-if="model === license.id"
                            class="h-3.5 w-3.5"
                        />
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="font-semibold">
                                    {{ license.name }}
                                </div>

                                <p
                                    v-if="license.description"
                                    class="mt-1 text-sm leading-6 text-stone-600 dark:text-stone-400"
                                >
                                    {{ license.description }}
                                </p>
                            </div>

                            <div class="shrink-0 text-lg font-semibold">
                                {{
                                    formatPrice(
                                        license.price_cents,
                                        license.currency,
                                    )
                                }}
                            </div>
                        </div>

                        <div
                            v-if="model === license.id"
                            class="mt-4 grid gap-2 border-t border-stone-200 pt-4 text-xs text-stone-600 sm:grid-cols-2 dark:border-stone-700 dark:text-stone-400"
                        >
                            <div class="flex items-center gap-2">
                                <ImageIcon class="h-4 w-4" />
                                {{ resolutionLabel(license.max_resolution) }}
                            </div>

                            <div class="flex items-center gap-2">
                                <Download class="h-4 w-4" />
                                {{ downloadsLabel(license.download_limit) }}
                            </div>

                            <div class="sm:col-span-2">
                                {{ expirationLabel(license.expires_after_days) }}
                            </div>
                        </div>
                    </div>
                </div>
            </label>
        </div>

        <div
            v-if="selectedLicense"
            class="mt-5 rounded-2xl bg-stone-100 p-4 dark:bg-stone-800/70"
        >
            <div class="flex items-center justify-between gap-4">
                <span class="text-sm text-stone-600 dark:text-stone-400">
                    Selected license
                </span>

                <span class="font-semibold">
                    {{ selectedLicense.name }}
                </span>
            </div>

            <div class="mt-2 flex items-center justify-between gap-4">
                <span class="text-sm text-stone-600 dark:text-stone-400">
                    Price
                </span>

                <span class="text-xl font-semibold">
                    {{
                        formatPrice(
                            selectedLicense.price_cents,
                            selectedLicense.currency,
                        )
                    }}
                </span>
            </div>
        </div>
    </fieldset>
</template>

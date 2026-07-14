<script setup lang="ts">
import {
    Check,
    Download,
    Package,
} from '@lucide/vue';

import AssetFormatBadge from './AssetFormatBadge.vue';

import type { PublicAssetOffering } from '@/types/publicAsset';

withDefaults(
    defineProps<{
        offering: PublicAssetOffering;
        selected?: boolean;
        featured?: boolean;
        selectable?: boolean;
    }>(),
    {
        selected: false,
        featured: false,
        selectable: true,
    },
);

const emit = defineEmits<{
    select: [offeringId: number];
}>();

function money(cents: number, currency: string): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
    }).format(cents / 100);
}

function bytes(value: number): string {
    if (!value) {
        return 'Size varies';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(
        Math.floor(Math.log(value) / Math.log(1024)),
        units.length - 1,
    );

    return `${(value / 1024 ** index).toFixed(index ? 1 : 0)} ${units[index]}`;
}
</script>

<template>
    <article
        :class="[
            'group relative flex h-full flex-col rounded-2xl border bg-white p-5 transition duration-200 dark:bg-stone-900',
            selectable
                ? 'cursor-pointer hover:border-stone-400 hover:shadow-md dark:hover:border-stone-600'
                : '',
            selected
                ? 'border-[var(--brand-primary)] shadow-md ring-2 ring-[var(--brand-primary)]/15'
                : featured
                    ? 'border-[var(--brand-accent)]'
                    : 'border-stone-200 dark:border-stone-800',
        ]"
        :tabindex="selectable ? 0 : undefined"
        :aria-pressed="selectable ? selected : undefined"
        @click="selectable && emit('select', offering.id)"
        @keydown.enter.prevent="selectable && emit('select', offering.id)"
        @keydown.space.prevent="selectable && emit('select', offering.id)"
    >
        <span
            v-if="selected"
            class="absolute -top-3 left-5 rounded-full bg-[var(--brand-primary)] px-3 py-1 text-[11px] font-semibold text-white"
        >
            Selected
        </span>

        <span
            v-else-if="featured"
            class="absolute -top-3 left-5 rounded-full bg-[var(--brand-accent)] px-3 py-1 text-[11px] font-semibold text-white"
        >
            Most popular
        </span>

        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p
                    class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[var(--brand-accent)]"
                >
                    {{ offering.license_type.name }}
                </p>

                <h3 class="mt-1 text-lg font-semibold">
                    {{ offering.name }}
                </h3>
            </div>

            <p class="shrink-0 text-xl font-bold">
                {{ money(offering.price_cents, offering.currency) }}
            </p>
        </div>

        <p
            v-if="offering.description || offering.license_type.description"
            class="mt-3 line-clamp-3 text-sm leading-6 text-stone-600 dark:text-stone-300"
        >
            {{ offering.description || offering.license_type.description }}
        </p>

        <div class="mt-4 flex flex-wrap gap-2">
            <AssetFormatBadge
                v-for="format in [
                    ...new Set(
                        offering.files.map((file) => file.extension),
                    ),
                ]"
                :key="format"
                :format="format"
            />
        </div>

        <ul
            v-if="offering.files.length"
            class="mt-4 flex-1 space-y-2 text-sm"
        >
            <li
                v-for="file in offering.files.slice(0, 3)"
                :key="file.id"
                class="flex items-start gap-2"
            >
                <Check
                    class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600"
                />

                <span class="min-w-0">
                    <span class="font-medium">
                        {{ file.role_label }}
                    </span>

                    <span
                        class="block truncate text-xs text-stone-500"
                    >
                        {{ file.original_filename }}
                    </span>
                </span>
            </li>
        </ul>

        <div
            class="mt-4 grid grid-cols-2 gap-3 rounded-xl bg-stone-100 p-3 text-sm dark:bg-stone-800/70"
        >
            <div class="flex items-center gap-2">
                <Package class="h-4 w-4" />

                <div>
                    <strong>{{ offering.files.length }}</strong>
                    <span class="block text-[11px] text-stone-500">
                        included files
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Download class="h-4 w-4" />

                <div>
                    <strong>{{ bytes(offering.total_size_bytes) }}</strong>
                    <span class="block text-[11px] text-stone-500">
                        package size
                    </span>
                </div>
            </div>
        </div>

        <button
            v-if="selectable"
            type="button"
            :class="[
                'mt-4 h-10 rounded-full px-4 text-sm font-semibold transition',
                selected
                    ? 'bg-[var(--brand-primary)] text-white'
                    : 'border border-[var(--brand-primary)] text-[var(--brand-primary)] hover:bg-[var(--brand-primary)] hover:text-white',
            ]"
            @click.stop="emit('select', offering.id)"
        >
            {{ selected ? 'Selected package' : 'Choose package' }}
        </button>

        <p class="mt-3 text-center text-[11px] text-stone-500">
            {{
                offering.download_limit === null
                    ? 'Unlimited downloads'
                    : `${offering.download_limit} downloads`
            }}
            ·
            {{
                offering.expires_after_days === null
                    ? 'No expiration'
                    : `${offering.expires_after_days} days`
            }}
        </p>
    </article>
</template>

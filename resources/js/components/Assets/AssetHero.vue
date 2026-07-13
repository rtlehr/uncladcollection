<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    title: string;
    collectionName?: string | null;
    backHref?: string;
    backLabel?: string;
}>();
</script>

<template>
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <Link
                :href="backHref ?? '/images'"
                class="text-sm text-muted-foreground transition hover:text-foreground hover:underline"
            >
                ← {{ backLabel ?? 'Back to Marketplace' }}
            </Link>

            <h1 class="mt-2 break-words text-3xl font-semibold">
                {{ title }}
            </h1>

            <p
                v-if="collectionName"
                class="mt-1 text-sm text-muted-foreground"
            >
                {{ collectionName }}
            </p>
        </div>

        <div
            v-if="$slots.actions"
            class="flex shrink-0 flex-wrap items-center justify-start gap-2 sm:justify-end"
        >
            <slot name="actions" />
        </div>
    </header>
</template>

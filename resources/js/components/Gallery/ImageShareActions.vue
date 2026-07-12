<script setup lang="ts">
import {
    Check,
    Copy,
    Share2,
} from '@lucide/vue';
import { ref } from 'vue';

const props = defineProps<{
    title: string;
    url?: string | null;
}>();

const copied = ref(false);

function currentUrl(): string {
    return props.url || window.location.href;
}

async function copyLink(): Promise<void> {
    await navigator.clipboard.writeText(currentUrl());

    copied.value = true;

    window.setTimeout(() => {
        copied.value = false;
    }, 1800);
}

async function share(): Promise<void> {
    if (navigator.share) {
        await navigator.share({
            title: props.title,
            url: currentUrl(),
        });

        return;
    }

    await copyLink();
}
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <button
            type="button"
            class="inline-flex h-10 items-center gap-2 rounded-full border border-stone-300 px-4 text-sm font-semibold transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] dark:border-stone-700"
            @click="share"
        >
            <Share2 class="h-4 w-4" />
            Share
        </button>

        <button
            type="button"
            class="inline-flex h-10 items-center gap-2 rounded-full border border-stone-300 px-4 text-sm font-semibold transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] dark:border-stone-700"
            @click="copyLink"
        >
            <Check
                v-if="copied"
                class="h-4 w-4 text-emerald-600"
            />

            <Copy
                v-else
                class="h-4 w-4"
            />

            {{ copied ? 'Copied' : 'Copy Link' }}
        </button>
    </div>
</template>

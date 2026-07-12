<script setup lang="ts">
import {
    Check,
    Copy,
    Mail,
    Share2,
} from '@lucide/vue';
import { ref } from 'vue';

const props = defineProps<{
    title: string;
    description?: string | null;
    url?: string | null;
}>();

const copied = ref(false);

function currentUrl(): string {
    return props.url || window.location.href;
}

function encodedUrl(): string {
    return encodeURIComponent(currentUrl());
}

function encodedText(): string {
    return encodeURIComponent(props.title);
}

async function copyLink(): Promise<void> {
    await navigator.clipboard.writeText(currentUrl());

    copied.value = true;

    window.setTimeout(() => {
        copied.value = false;
    }, 1800);
}

async function nativeShare(): Promise<void> {
    if (navigator.share) {
        await navigator.share({
            title: props.title,
            text: props.description ?? undefined,
            url: currentUrl(),
        });

        return;
    }

    await copyLink();
}

function openPopup(url: string): void {
    window.open(
        url,
        'share-window',
        'width=720,height=520,noopener,noreferrer',
    );
}
</script>

<template>
    <div
        class="flex flex-wrap gap-2"
        aria-label="Share this page"
    >
        <button
            type="button"
            class="inline-flex h-10 items-center gap-2 rounded-full border border-stone-300 px-4 text-sm font-semibold transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] dark:border-stone-700"
            @click="nativeShare"
        >
            <Share2 class="h-4 w-4" />
            Share
        </button>

        <button
            type="button"
            class="hidden h-10 w-10 items-center justify-center rounded-full border border-stone-300 transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] sm:inline-flex dark:border-stone-700"
            aria-label="Share on Facebook"
            @click="openPopup(`https://www.facebook.com/sharer/sharer.php?u=${encodedUrl()}`)"
        >
            <span aria-hidden="true" class="text-sm font-bold">f</span>
        </button>

        <button
            type="button"
            class="hidden h-10 items-center justify-center rounded-full border border-stone-300 px-3 text-sm font-bold transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] sm:inline-flex dark:border-stone-700"
            aria-label="Share on X"
            @click="openPopup(`https://x.com/intent/post?url=${encodedUrl()}&text=${encodedText()}`)"
        >
            X
        </button>

        <a
            :href="`mailto:?subject=${encodedText()}&body=${encodedUrl()}`"
            class="hidden h-10 w-10 items-center justify-center rounded-full border border-stone-300 transition hover:border-[var(--brand-accent)] hover:text-[var(--brand-accent)] sm:inline-flex dark:border-stone-700"
            aria-label="Share by email"
        >
            <Mail class="h-4 w-4" />
        </a>

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

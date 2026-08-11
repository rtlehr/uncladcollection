<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

type AdPayload = {
    placement: { code: string; name: string; format: string; width?: number | null; height?: number | null };
    creative: { id: number; type: string; media_url: string; mime_type?: string | null; headline?: string | null; body?: string | null; cta_label?: string | null; destination_url: string; alt_text: string };
    campaign: { public_code: string; name: string };
};

const props = withDefaults(defineProps<{ placement: string; class?: string; preview?: boolean }>(), { preview: false });
const emit = defineEmits<{
    availability: [hasAd: boolean];
}>();
const ad = ref<AdPayload | null>(null);
const loading = ref(true);
const rootClass = computed(() => props.class ?? '');

function csrf(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

async function track(kind: 'impression' | 'click'): Promise<void> {
    if (!ad.value || props.preview) {
return;
}

    await fetch(`/ads/creatives/${ad.value.creative.id}/${kind}`, {
        method: 'POST', credentials: 'same-origin', keepalive: true,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
        body: JSON.stringify({ placement_code: props.placement }),
    }).catch(() => undefined);
}

async function load(): Promise<void> {
    try {
        const response = await fetch(`/ads/placements/${encodeURIComponent(props.placement)}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });

        if (response.status === 204) {
            emit('availability', false);
            return;
        }

        if (response.ok) {
            ad.value = await response.json();
            emit('availability', true);
            await track('impression');
            return;
        }

        emit('availability', false);
    } finally {
 loading.value = false; 
}
}

function openAd(): void {
    if (!ad.value) {
return;
}

    void track('click');
    const url = ad.value.creative.destination_url;

    if (url.startsWith('/')) {
window.location.href = url;
} else {
window.open(url, '_blank', 'noopener,noreferrer');
}
}

onMounted(load);
</script>

<template>
    <div v-if="ad" :class="rootClass" class="public-ad-placement" :data-placement="placement">
        <article class="relative overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-800 dark:bg-stone-900">
            <button type="button" class="block w-full text-left" :aria-label="`Sponsored: ${ad.creative.alt_text}`" @click="openAd">
                <video v-if="ad.creative.type === 'video'" :src="ad.creative.media_url" muted playsinline loop autoplay class="h-auto w-full object-cover" />
                <img v-else :src="ad.creative.media_url" :alt="ad.creative.alt_text" loading="lazy" class="h-auto w-full object-cover" />
                <div v-if="ad.creative.headline || ad.creative.body || ad.creative.cta_label" class="flex items-center justify-between gap-4 p-4">
                    <div><p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-500">Sponsored</p><h3 v-if="ad.creative.headline" class="mt-1 font-semibold">{{ ad.creative.headline }}</h3><p v-if="ad.creative.body" class="mt-1 text-sm text-stone-600 dark:text-stone-300">{{ ad.creative.body }}</p></div>
                    <span v-if="ad.creative.cta_label" class="shrink-0 rounded-full bg-stone-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-stone-900">{{ ad.creative.cta_label }}</span>
                </div>
            </button>
        </article>
    </div>
    <div v-else-if="loading && preview" :class="rootClass" class="rounded-2xl border border-dashed p-6 text-center text-sm text-stone-500">Loading advertisement…</div>
</template>

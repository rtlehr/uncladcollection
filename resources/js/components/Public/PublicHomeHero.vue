<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Play, Search, Sparkles } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';

import PerformanceImage from '@/components/Public/PerformanceImage.vue';
import type { HomeHeroCampaign, HomeHeroImage } from '@/types/home';

const props = withDefaults(defineProps<{
    eyebrow?: string | null;
    title: string;
    description?: string | null;
    heroImage?: HomeHeroImage | null;
    campaign?: HomeHeroCampaign | null;
    primaryLabel?: string;
    primaryHref?: string;
    secondaryLabel?: string;
    secondaryHref?: string;
}>(), {
    eyebrow: null, description: null, heroImage: null, campaign: null,
    primaryLabel: 'Browse Marketplace', primaryHref: '/images',
    secondaryLabel: 'Read Stories', secondaryHref: '/blog',
});

const video = ref<HTMLVideoElement | null>(null);
const canAutoplay = ref(false);
const manuallyPlaying = ref(false);

const effectiveEyebrow = computed(() => props.campaign?.eyebrow || props.eyebrow);
const effectiveTitle = computed(() => props.campaign?.headline || props.title);
const effectiveDescription = computed(() => props.campaign?.subheadline || props.description);
const effectivePrimaryLabel = computed(() => props.campaign?.primary_button_label || props.primaryLabel);
const effectivePrimaryHref = computed(() => props.campaign?.primary_button_url || props.primaryHref);
const effectiveSecondaryLabel = computed(() => props.campaign?.secondary_button_label || props.secondaryLabel);
const effectiveSecondaryHref = computed(() => props.campaign?.secondary_button_url || props.secondaryHref);
const mediaPosition = computed(() => ({ center: 'object-center', top: 'object-top', bottom: 'object-bottom', left: 'object-left', right: 'object-right' }[props.campaign?.media_position ?? 'center']));
const heightClass = computed(() => ({ compact: 'min-h-[520px]', medium: 'min-h-[620px]', large: 'min-h-[720px]', fullscreen: 'min-h-[calc(100vh-5rem)]' }[props.campaign?.hero_height ?? 'large']));
const textClass = computed(() => ({ left: 'text-left items-start', center: 'text-center items-center', right: 'text-right items-end' }[props.campaign?.text_alignment ?? 'left']));

onMounted(async () => {
    if (!props.campaign || props.campaign.media_type !== 'video' || !props.campaign.autoplay_first_visit) return;
    const played = sessionStorage.getItem('unclad-home-hero-played') === '1';
    const mobile = window.matchMedia('(max-width: 767px)').matches;
    canAutoplay.value = !played && (!mobile || props.campaign.autoplay_mobile);
    if (canAutoplay.value && video.value) {
        try {
            await video.value.play();
            sessionStorage.setItem('unclad-home-hero-played', '1');
        } catch { canAutoplay.value = false; }
    }
});

async function playVideo(): Promise<void> {
    if (!video.value) return;
    manuallyPlaying.value = true;
    await video.value.play();
    sessionStorage.setItem('unclad-home-hero-played', '1');
}
</script>

<template>
    <section :class="['relative overflow-hidden border-b border-stone-200 dark:border-stone-800', heightClass]">
        <template v-if="campaign?.media_url">
            <video
                v-if="campaign.media_type === 'video'"
                ref="video"
                :src="campaign.media_url"
                :poster="campaign.poster_url ?? undefined"
                :loop="campaign.loop_video"
                muted playsinline preload="metadata"
                :class="['absolute inset-0 h-full w-full object-cover', mediaPosition]"
            />
            <img v-else :src="campaign.media_url" :alt="campaign.name" :class="['absolute inset-0 h-full w-full object-cover', mediaPosition]" fetchpriority="high" />
            <div class="absolute inset-0 bg-black" :style="{ opacity: campaign.overlay_opacity / 100 }" />
        </template>
        <div v-else class="absolute inset-0 bg-[radial-gradient(circle_at_12%_12%,color-mix(in_srgb,var(--brand-accent)_16%,transparent),transparent_34%),radial-gradient(circle_at_88%_14%,color-mix(in_srgb,var(--brand-secondary)_18%,transparent),transparent_38%)]" />

        <div class="relative mx-auto grid h-full max-w-[1440px] gap-12 px-5 py-16 sm:px-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:px-12 lg:py-24">
            <div :class="['flex max-w-2xl flex-col', textClass, campaign ? 'text-white' : '']">
                <div v-if="effectiveEyebrow" class="inline-flex items-center gap-2 rounded-full border border-current/25 bg-black/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] backdrop-blur">
                    <Sparkles class="h-3.5 w-3.5" /> {{ effectiveEyebrow }}
                </div>
                <h1 class="mt-6 text-4xl font-semibold leading-[1.02] tracking-[-0.045em] sm:text-6xl lg:text-7xl">{{ effectiveTitle }}</h1>
                <p v-if="effectiveDescription" class="mt-6 max-w-xl text-base leading-8 opacity-85 sm:text-lg">{{ effectiveDescription }}</p>

                <form v-if="campaign?.show_search ?? true" action="/images" method="get" class="mt-8 flex w-full max-w-xl items-center gap-2 rounded-full border border-white/25 bg-white/95 p-2 text-stone-900 shadow-xl" role="search">
                    <Search class="ml-3 h-5 w-5 text-stone-400" /><input name="search" type="search" placeholder="Search the marketplace..." class="min-w-0 flex-1 border-0 bg-transparent px-2 py-2 text-sm outline-none" />
                    <button class="h-11 rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white">Search</button>
                </form>

                <div class="mt-6 flex flex-wrap gap-3">
                    <Link :href="effectivePrimaryHref" class="inline-flex h-12 items-center gap-2 rounded-full bg-[var(--brand-primary)] px-6 text-sm font-semibold text-white">{{ effectivePrimaryLabel }}<ArrowRight class="h-4 w-4" /></Link>
                    <Link v-if="effectiveSecondaryLabel" :href="effectiveSecondaryHref" class="inline-flex h-12 items-center rounded-full border border-current/30 bg-white/10 px-6 text-sm font-semibold backdrop-blur">{{ effectiveSecondaryLabel }}</Link>
                </div>
            </div>

            <button v-if="campaign?.media_type === 'video' && !canAutoplay && !manuallyPlaying" type="button" class="mx-auto flex h-20 w-20 items-center justify-center rounded-full border border-white/40 bg-black/30 text-white backdrop-blur transition hover:scale-105" aria-label="Play welcome video" @click="playVideo"><Play class="ml-1 h-8 w-8" /></button>

            <Link v-else-if="!campaign && heroImage?.image_url" :href="`/images/${heroImage.slug}`" class="relative min-h-[420px] overflow-hidden rounded-[2.25rem] bg-stone-200 shadow-xl">
                <PerformanceImage :src="heroImage.image_url" :alt="heroImage.title" loading="eager" fetchpriority="high" wrapper-class="absolute inset-0" image-class="object-cover" />
            </Link>
        </div>
    </section>
</template>

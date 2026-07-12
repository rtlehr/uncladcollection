<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';

withDefaults(defineProps<{
    title: string;
    description?: string | null;
    primaryLabel?: string;
    primaryHref?: string;
    secondaryLabel?: string | null;
    secondaryHref?: string | null;
}>(), {
    description: null,
    primaryLabel: 'Get Started',
    primaryHref: '/',
    secondaryLabel: null,
    secondaryHref: null,
});
</script>

<template>
    <section class="px-5 py-16 sm:px-8 lg:px-12 lg:py-20">
        <div class="mx-auto max-w-5xl overflow-hidden rounded-[2rem] bg-[var(--brand-primary)] px-6 py-12 text-center text-white shadow-2xl sm:px-12 sm:py-16">
            <h2 class="mx-auto max-w-3xl text-3xl font-semibold tracking-tight sm:text-5xl">
                {{ title }}
            </h2>

            <p v-if="description" class="mx-auto mt-5 max-w-2xl text-base leading-7 text-white/75">
                {{ description }}
            </p>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <Link
                    :href="primaryHref"
                    class="inline-flex h-12 items-center gap-2 rounded-full bg-white px-6 text-sm font-semibold text-[var(--brand-primary)] transition hover:-translate-y-0.5"
                >
                    {{ primaryLabel }}
                    <ArrowRight class="h-4 w-4" />
                </Link>

                <Link
                    v-if="secondaryLabel && secondaryHref"
                    :href="secondaryHref"
                    class="inline-flex h-12 items-center rounded-full border border-white/30 px-6 text-sm font-semibold text-white transition hover:bg-white/10"
                >
                    {{ secondaryLabel }}
                </Link>
            </div>
        </div>
    </section>
</template>

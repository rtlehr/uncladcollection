<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Search, Sparkles } from '@lucide/vue';

import type { HomeHeroImage } from '@/types/home';

withDefaults(
    defineProps<{
        eyebrow?: string | null;
        title: string;
        description?: string | null;
        heroImage?: HomeHeroImage | null;
        primaryLabel?: string;
        primaryHref?: string;
        secondaryLabel?: string;
        secondaryHref?: string;
    }>(),
    {
        eyebrow: null,
        description: null,
        heroImage: null,
        primaryLabel: 'Browse Images',
        primaryHref: '/images',
        secondaryLabel: 'Read Stories',
        secondaryHref: '/blog',
    },
);
</script>

<template>
    <section class="relative overflow-hidden border-b border-stone-200 dark:border-stone-800">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_12%_12%,color-mix(in_srgb,var(--brand-accent)_16%,transparent),transparent_34%),radial-gradient(circle_at_88%_14%,color-mix(in_srgb,var(--brand-secondary)_18%,transparent),transparent_38%)]" />

        <div class="relative mx-auto grid min-h-[680px] max-w-[1440px] gap-12 px-4 py-12 sm:px-8 sm:py-20 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:px-12 lg:py-24">
            <div class="max-w-2xl">
                <div
                    v-if="eyebrow"
                    class="inline-flex items-center gap-2 rounded-2xl border sm:rounded-full border-stone-300 bg-white/75 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-accent)] backdrop-blur dark:border-stone-700 dark:bg-stone-900/75"
                >
                    <Sparkles class="h-3.5 w-3.5" aria-hidden="true" />
                    {{ eyebrow }}
                </div>

                <h1 class="mt-6 break-words text-3xl font-semibold sm:text-4xl leading-[1.02] tracking-[-0.045em] sm:text-6xl lg:text-7xl">
                    {{ title }}
                </h1>

                <p
                    v-if="description"
                    class="mt-6 max-w-xl text-base leading-8 text-stone-600 sm:text-lg dark:text-stone-300"
                >
                    {{ description }}
                </p>

                <form
                    action="/images"
                    method="get"
                    class="mt-8 flex max-w-xl items-center gap-2 rounded-2xl border sm:rounded-full border-stone-300 bg-white p-2 shadow-xl shadow-stone-950/5 dark:border-stone-700 dark:bg-stone-900"
                    role="search"
                >
                    <Search class="ml-3 h-5 w-5 shrink-0 text-stone-400" aria-hidden="true" />
                    <label for="homepage-search" class="sr-only">Search images</label>
                    <input
                        id="homepage-search"
                        name="search"
                        type="search"
                        placeholder="Search the image library..."
                        class="min-w-0 flex-1 border-0 bg-transparent px-2 py-2 text-sm outline-none placeholder:text-stone-400"
                    />
                    <button
                        type="submit"
                        class="inline-flex h-11 items-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white transition hover:opacity-90"
                    >
                        Search
                    </button>
                </form>

                <div class="mt-6 flex flex-wrap gap-3">
                    <Link
                        :href="primaryHref"
                        class="inline-flex h-12 items-center gap-2 rounded-full bg-[var(--brand-primary)] px-6 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:opacity-90"
                    >
                        {{ primaryLabel }}
                        <ArrowRight class="h-4 w-4" aria-hidden="true" />
                    </Link>

                    <Link
                        :href="secondaryHref"
                        class="inline-flex h-12 items-center rounded-2xl border sm:rounded-full border-stone-300 bg-white/75 px-6 text-sm font-semibold transition hover:bg-white dark:border-stone-700 dark:bg-stone-900/75 dark:hover:bg-stone-900"
                    >
                        {{ secondaryLabel }}
                    </Link>
                </div>
            </div>

            <Link
                v-if="heroImage?.image_url"
                :href="`/images/${heroImage.slug}`"
                class="group relative overflow-hidden rounded-[2.25rem] bg-stone-200 shadow-2xl shadow-stone-950/20 dark:bg-stone-800"
            >
                <img
                    :src="heroImage.image_url"
                    :alt="heroImage.title"
                    fetchpriority="high"
                    decoding="async"
                    class="aspect-[4/3] h-full w-full object-cover transition duration-700 group-hover:scale-[1.025]"
                />

                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/10 to-transparent" />

                <div class="absolute inset-x-0 bottom-0 p-6 text-white sm:p-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/65">
                        Featured image
                    </p>
                    <h2 class="mt-2 text-2xl font-semibold sm:text-3xl">
                        {{ heroImage.title }}
                    </h2>
                    <p v-if="heroImage.photographer" class="mt-2 text-sm text-white/75">
                        Photography by {{ heroImage.photographer }}
                    </p>
                </div>
            </Link>

            <div
                v-else
                class="flex aspect-[4/3] items-end overflow-hidden rounded-[2.25rem] bg-[linear-gradient(135deg,color-mix(in_srgb,var(--brand-secondary)_76%,white),color-mix(in_srgb,var(--brand-primary)_88%,black))] p-8 text-white shadow-2xl"
            >
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/65">Featured imagery</p>
                    <h2 class="mt-3 text-3xl font-semibold">Natural. Respectful. Real.</h2>
                    <p class="mt-3 max-w-md text-sm leading-7 text-white/75">
                        Select a homepage hero image in Site Settings, or allow the site to choose one automatically.
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>

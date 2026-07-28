<script lang="ts">
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';
export default { layout: PublicBlankLayout };
</script>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download, Heart, KeyRound, LibraryBig } from '@lucide/vue';
import AccountPageLayout from '@/components/Account/AccountPageLayout.vue';
import GalleryGrid from '@/components/Gallery/GalleryGrid.vue';

defineProps<{
    summary: { licenses: number; active_licenses: number; favorites: number; downloads_used: number };
    alerts: Array<{ type: string; title: string; message: string; href: string }>;
    recent_licenses: Array<{ id: number; title: string; license_name: string; status: string; purchased_at: string | null; preview_url: string | null; detail_url: string; order_number: string | null }>;
    recently_viewed: any[];
    recommendations: any[];
}>();

const cards = [
    { key: 'licenses', label: 'Licensed assets', icon: LibraryBig, href: '/account/library' },
    { key: 'active_licenses', label: 'Active licenses', icon: KeyRound, href: '/account/library' },
    { key: 'favorites', label: 'Saved assets', icon: Heart, href: '/account/wish-lists' },
    { key: 'downloads_used', label: 'Downloads used', icon: Download, href: '/account/library' },
] as const;
</script>

<template>
    <Head title="My Account" />
    <AccountPageLayout>
        <template #title>Welcome back</template>
        <template #description>Your purchases, saved assets, and personalized marketplace activity are together in one public account area.</template>

        <div class="space-y-10">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Account summary">
                <Link v-for="card in cards" :key="card.key" :href="card.href" class="rounded-2xl border border-stone-200 bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-md dark:border-stone-800 dark:bg-stone-900">
                    <component :is="card.icon" class="h-5 w-5 text-[var(--brand-accent)]" />
                    <p class="mt-5 text-3xl font-semibold">{{ summary[card.key] }}</p>
                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ card.label }}</p>
                </Link>
            </section>

            <section v-if="alerts.length" class="space-y-3" aria-labelledby="account-alerts-title">
                <h2 id="account-alerts-title" class="text-xl font-semibold">Needs your attention</h2>
                <Link v-for="alert in alerts" :key="alert.title" :href="alert.href" class="block rounded-2xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-900 dark:bg-amber-950/30">
                    <p class="font-semibold">{{ alert.title }}</p><p class="mt-1 text-sm leading-6">{{ alert.message }}</p>
                </Link>
            </section>

            <section aria-labelledby="recent-library-title">
                <div class="mb-4 flex items-end justify-between gap-4"><div><h2 id="recent-library-title" class="text-xl font-semibold">Recent purchases</h2><p class="mt-1 text-sm text-stone-600 dark:text-stone-400">Your latest licenses and proof of ownership.</p></div><Link href="/account/library" class="text-sm font-semibold text-[var(--brand-accent)]">View library</Link></div>
                <div v-if="recent_licenses.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Link v-for="license in recent_licenses" :key="license.id" :href="license.detail_url" class="overflow-hidden rounded-2xl border border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
                        <div class="aspect-[4/3] bg-stone-100 dark:bg-stone-800"><img v-if="license.preview_url" :src="license.preview_url" :alt="license.title" class="h-full w-full object-cover" /></div>
                        <div class="p-4"><p class="truncate font-semibold">{{ license.title }}</p><p class="mt-1 text-xs text-stone-500">{{ license.license_name }} · {{ license.purchased_at }}</p></div>
                    </Link>
                </div>
                <div v-else class="rounded-2xl border border-dashed border-stone-300 p-8 text-center dark:border-stone-700"><p class="font-semibold">Your library is ready when you are.</p><Link href="/images" class="mt-4 inline-flex rounded-full bg-[var(--brand-primary)] px-5 py-3 text-sm font-semibold text-white">Browse marketplace</Link></div>
            </section>

            <section v-if="recently_viewed.length" aria-labelledby="recently-viewed-title"><h2 id="recently-viewed-title" class="mb-4 text-xl font-semibold">Recently viewed</h2><GalleryGrid :assets="recently_viewed" /></section>
            <section v-if="recommendations.length" aria-labelledby="recommendations-title"><h2 id="recommendations-title" class="mb-4 text-xl font-semibold">Recommended for you</h2><GalleryGrid :assets="recommendations" /></section>
        </div>
    </AccountPageLayout>
</template>

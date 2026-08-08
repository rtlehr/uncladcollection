<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, Heart, Images, LibraryBig, LockKeyhole, LifeBuoy, PackageSearch, ShieldCheck, UserRound } from '@lucide/vue';
import { computed } from 'vue';
import PublicPageLayout from '@/components/Public/PublicPageLayout.vue';

const page = usePage();
const currentUrl = computed(() => page.url.split('?')[0]);

const items = [
    { label: 'Overview', href: '/account', icon: UserRound },
    { label: 'My Library', href: '/account/library', icon: LibraryBig },
    { label: 'My Orders', href: '/account/orders', icon: PackageSearch },
    { label: 'My Designs', href: '/account/designs', icon: Images },
    { label: 'Wish Lists', href: '/account/wish-lists', icon: Heart },
    { label: 'Profile', href: '/settings/profile', icon: UserRound },
    { label: 'Security', href: '/settings/security', icon: LockKeyhole },
    { label: 'Privacy', href: '/account/privacy', icon: ShieldCheck },
    { label: 'Notifications', href: '/account/notifications', icon: Bell },
    { label: 'My Tickets', href: '/support/tickets', icon: LifeBuoy },
];

function active(href: string): boolean {
    if (href === '/account') {
return currentUrl.value === href;
}

    return currentUrl.value.startsWith(href);
}
</script>

<template>
    <PublicPageLayout>
        <section class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
            <div class="mx-auto max-w-[1440px] px-5 py-10 sm:px-8 lg:px-12">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-accent)]">Member account</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-[-0.035em] sm:text-4xl"><slot name="title">My Account</slot></h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-600 dark:text-stone-400"><slot name="description">Manage your purchases, saved assets, profile, and security.</slot></p>
            </div>
        </section>

        <div class="mx-auto grid max-w-[1440px] gap-8 px-4 py-8 sm:px-8 lg:grid-cols-[230px_minmax(0,1fr)] lg:px-12 lg:py-12">
            <aside>
                <nav class="flex gap-2 overflow-x-auto pb-2 lg:sticky lg:top-24 lg:flex-col" aria-label="Account navigation">
                    <Link v-for="item in items" :key="item.href" :href="item.href"
                        class="inline-flex min-h-11 shrink-0 items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition"
                        :class="active(item.href) ? 'bg-[var(--brand-primary)] text-white' : 'border border-stone-200 bg-white hover:bg-stone-100 dark:border-stone-800 dark:bg-stone-900 dark:hover:bg-stone-800'">
                        <component :is="item.icon" class="h-4 w-4" aria-hidden="true" />{{ item.label }}
                    </Link>
                </nav>
            </aside>
            <main class="min-w-0"><slot /></main>
        </div>
    </PublicPageLayout>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, X } from '@lucide/vue';
import {
    computed,
    onBeforeUnmount,
    watch,
    ref,
} from 'vue';

import PublicCartMenu from '@/components/Public/PublicCartMenu.vue';
import { dashboard, login, register } from '@/routes';

const page = usePage();
const mobileMenuOpen = ref(false);

const site = computed(() => (page.props.site ?? {}) as Record<string, any>);
const isAuthenticated = computed(() =>
    Boolean((page.props.auth as any)?.user),
);
const siteName = computed(() =>
    site.value.name || 'Unclad Collection',
);

function closeMenu(): void {
    mobileMenuOpen.value = false;
}

function handleEscape(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        closeMenu();
    }
}

watch(mobileMenuOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';

    if (open) {
        document.addEventListener('keydown', handleEscape);
    } else {
        document.removeEventListener('keydown', handleEscape);
    }
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
    document.removeEventListener('keydown', handleEscape);
});
</script>

<template>
    <header class="public-glass sticky top-0 z-50 border-b border-stone-200/80 dark:border-stone-800 dark:bg-stone-950/95">
        <div class="mx-auto flex h-16 max-w-[1440px] items-center justify-between gap-3 px-4 sm:h-18 sm:px-8 lg:px-12">
            <Link
                href="/"
                class="flex min-w-0 items-center gap-3"
                :aria-label="`${siteName} home`"
                @click="closeMenu"
            >
                <img
                    v-if="site.logo_url"
                    :src="site.logo_url"
                    :alt="`${siteName} logo`"
                    class="h-8 max-w-[150px] object-contain sm:h-10 sm:max-w-52"
                />

                <span
                    v-else
                    class="truncate text-lg font-semibold tracking-tight sm:text-xl"
                >
                    {{ siteName }}
                </span>
            </Link>

            <nav
                class="hidden items-center gap-8 text-sm font-medium lg:flex"
                aria-label="Primary navigation"
            >
                <Link href="/images" class="transition hover:text-[var(--brand-accent)]">
                    Images
                </Link>

                <Link href="/blog" class="transition hover:text-[var(--brand-accent)]">
                    Stories
                </Link>

                <Link href="/public-page" class="transition hover:text-[var(--brand-accent)]">
                    About
                </Link>

                <Link
                    v-if="isAuthenticated"
                    href="/favorites"
                    class="transition hover:text-[var(--brand-accent)]"
                >
                    Favorites
                </Link>
            </nav>

            <div class="hidden items-center gap-3 lg:flex">
                <PublicCartMenu />

                <Link
                    v-if="isAuthenticated"
                    :href="dashboard()"
                    class="inline-flex h-10 items-center rounded-full border border-stone-300 px-5 text-sm font-medium dark:border-stone-700"
                >
                    Dashboard
                </Link>

                <template v-else>
                    <Link :href="login()" class="px-3 py-2 text-sm font-medium">
                        Log in
                    </Link>

                    <Link
                        :href="register()"
                        class="inline-flex h-10 items-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-medium text-white"
                    >
                        Join the community
                    </Link>
                </template>
            </div>

            <div class="flex shrink-0 items-center gap-2 lg:hidden">
                <PublicCartMenu compact />

                <button
                    type="button"
                    class="public-touch-target inline-flex h-11 w-11 items-center justify-center rounded-full border border-stone-300 dark:border-stone-700"
                    :aria-expanded="mobileMenuOpen"
                    aria-controls="public-mobile-navigation"
                    aria-label="Toggle navigation"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                >
                    <X v-if="mobileMenuOpen" class="h-5 w-5" />
                    <Menu v-else class="h-5 w-5" />
                </button>
            </div>
        </div>

        <div
            v-if="mobileMenuOpen"
            id="public-mobile-navigation"
            class="public-mobile-menu fixed inset-x-0 top-16 z-50 max-h-[calc(100dvh-4rem)] overflow-y-auto border-t border-stone-200 bg-stone-50 px-4 py-4 shadow-xl sm:top-18 sm:px-8 lg:hidden dark:border-stone-800 dark:bg-stone-950"
        >
            <nav class="grid gap-1" aria-label="Mobile navigation">
                <Link
                    href="/images"
                    class="flex min-h-12 items-center rounded-xl px-4 py-3 font-medium hover:bg-stone-100 dark:hover:bg-stone-900"
                    @click="closeMenu"
                >
                    Images
                </Link>

                <Link
                    href="/blog"
                    class="flex min-h-12 items-center rounded-xl px-4 py-3 font-medium hover:bg-stone-100 dark:hover:bg-stone-900"
                    @click="closeMenu"
                >
                    Stories
                </Link>

                <Link
                    href="/public-page"
                    class="flex min-h-12 items-center rounded-xl px-4 py-3 font-medium hover:bg-stone-100 dark:hover:bg-stone-900"
                    @click="closeMenu"
                >
                    About
                </Link>

                <Link
                    href="/cart"
                    class="flex min-h-12 items-center rounded-xl px-4 py-3 font-medium hover:bg-stone-100 dark:hover:bg-stone-900"
                    @click="closeMenu"
                >
                    Shopping Cart
                </Link>

                <Link
                    v-if="isAuthenticated"
                    href="/favorites"
                    class="flex min-h-12 items-center rounded-xl px-4 py-3 font-medium hover:bg-stone-100 dark:hover:bg-stone-900"
                    @click="closeMenu"
                >
                    Favorites
                </Link>

                <div class="my-2 h-px bg-stone-200 dark:bg-stone-800" />

                <Link
                    v-if="isAuthenticated"
                    :href="dashboard()"
                    class="flex min-h-12 items-center justify-center rounded-full bg-[var(--brand-primary)] px-5 py-3 text-center font-medium text-white"
                    @click="closeMenu"
                >
                    Dashboard
                </Link>

                <template v-else>
                    <Link
                        :href="login()"
                        class="flex min-h-12 items-center rounded-xl px-4 py-3 font-medium"
                        @click="closeMenu"
                    >
                        Log in
                    </Link>

                    <Link
                        :href="register()"
                        class="flex min-h-12 items-center justify-center rounded-full bg-[var(--brand-primary)] px-5 py-3 text-center font-medium text-white"
                        @click="closeMenu"
                    >
                        Join the community
                    </Link>
                </template>
            </nav>
        </div>
    </header>
</template>

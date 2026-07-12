<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { dashboard, login, register } from '@/routes';

const page = usePage();
const mobileMenuOpen = ref(false);

const site = computed(() => (page.props.site ?? {}) as Record<string, any>);
const isAuthenticated = computed(() => Boolean((page.props.auth as any)?.user));
const siteName = computed(() => site.value.name || 'Unclad Collection');

function closeMenu() {
    mobileMenuOpen.value = false;
}
</script>

<template>
    <header class="sticky top-0 z-50 border-b border-stone-200/80 bg-stone-50/90 backdrop-blur-xl dark:border-stone-800 dark:bg-stone-950/90">
        <div class="mx-auto flex h-18 max-w-[1440px] items-center justify-between px-5 sm:px-8 lg:px-12">
            <Link href="/" class="flex items-center gap-3" :aria-label="`${siteName} home`">
                <img
                    v-if="site.logo_url"
                    :src="site.logo_url"
                    :alt="`${siteName} logo`"
                    class="h-10 max-w-52 object-contain"
                />
                <span v-else class="text-xl font-semibold tracking-tight">
                    {{ siteName }}
                </span>
            </Link>

            <nav class="hidden items-center gap-8 text-sm font-medium lg:flex" aria-label="Primary navigation">
                <Link href="/images" class="transition hover:text-[var(--brand-accent)]">Images</Link>
                <Link href="/blog" class="transition hover:text-[var(--brand-accent)]">Stories</Link>
                <Link href="/demo/public-page" class="transition hover:text-[var(--brand-accent)]">About</Link>
                <Link v-if="isAuthenticated" href="/favorites" class="transition hover:text-[var(--brand-accent)]">Favorites</Link>
            </nav>

            <div class="hidden items-center gap-3 lg:flex">
                <Link
                    v-if="isAuthenticated"
                    :href="dashboard()"
                    class="inline-flex h-10 items-center rounded-full border border-stone-300 px-5 text-sm font-medium dark:border-stone-700"
                >
                    Dashboard
                </Link>

                <template v-else>
                    <Link :href="login()" class="px-3 py-2 text-sm font-medium">Log in</Link>
                    <Link
                        :href="register()"
                        class="inline-flex h-10 items-center rounded-full bg-[var(--brand-primary)] px-5 text-sm font-medium text-white"
                    >
                        Join the community
                    </Link>
                </template>
            </div>

            <button
                type="button"
                class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-stone-300 lg:hidden dark:border-stone-700"
                :aria-expanded="mobileMenuOpen"
                aria-controls="public-mobile-navigation"
                aria-label="Toggle navigation"
                @click="mobileMenuOpen = !mobileMenuOpen"
            >
                <X v-if="mobileMenuOpen" class="h-5 w-5" />
                <Menu v-else class="h-5 w-5" />
            </button>
        </div>

        <div
            v-if="mobileMenuOpen"
            id="public-mobile-navigation"
            class="border-t border-stone-200 bg-stone-50 px-5 py-5 lg:hidden dark:border-stone-800 dark:bg-stone-950"
        >
            <nav class="grid gap-2" aria-label="Mobile navigation">
                <Link href="/images" class="rounded-lg px-3 py-3 font-medium" @click="closeMenu">Images</Link>
                <Link href="/blog" class="rounded-lg px-3 py-3 font-medium" @click="closeMenu">Stories</Link>
                <Link href="/demo/public-page" class="rounded-lg px-3 py-3 font-medium" @click="closeMenu">About</Link>
                <Link v-if="isAuthenticated" href="/favorites" class="rounded-lg px-3 py-3 font-medium" @click="closeMenu">Favorites</Link>

                <Link
                    v-if="isAuthenticated"
                    :href="dashboard()"
                    class="mt-2 rounded-full bg-[var(--brand-primary)] px-5 py-3 text-center font-medium text-white"
                    @click="closeMenu"
                >
                    Dashboard
                </Link>

                <template v-else>
                    <Link :href="login()" class="rounded-lg px-3 py-3 font-medium" @click="closeMenu">Log in</Link>
                    <Link
                        :href="register()"
                        class="rounded-full bg-[var(--brand-primary)] px-5 py-3 text-center font-medium text-white"
                        @click="closeMenu"
                    >
                        Join the community
                    </Link>
                </template>
            </nav>
        </div>
    </header>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { AtSign, Camera, Globe2, Video } from '@lucide/vue';
import { computed } from 'vue';
import { dashboard, login, register } from '@/routes';

const page = usePage();
const site = computed(() => (page.props.site ?? {}) as Record<string, any>);
const isAuthenticated = computed(() => Boolean((page.props.auth as any)?.user));
const siteName = computed(() => site.value.name || 'Unclad Collection');
const publicNavigation = computed(() => (page.props.public_page_navigation ?? {}) as Record<string, Array<{ label: string; href: string }>>);
const companyPages = computed(() => publicNavigation.value.footer_company ?? []);
const resourcePages = computed(() => publicNavigation.value.footer_resources ?? []);
const legalPages = computed(() => publicNavigation.value.footer_legal ?? []);

const socialLinks = computed(() => [
    { label: 'Instagram', href: site.value.social?.instagram_url || '', icon: Camera },
    { label: 'Facebook', href: site.value.social?.facebook_url || '', icon: Globe2 },
    { label: 'YouTube', href: site.value.social?.youtube_url || '', icon: Video },
    { label: 'X', href: site.value.social?.x_account_url || '', icon: AtSign },
].filter((item) => item.href));
</script>

<template>
    <footer class="border-t border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900">
        <div class="mx-auto grid max-w-[1440px] gap-10 px-5 py-12 sm:px-8 md:grid-cols-2 lg:grid-cols-5 lg:px-12">
            <div class="lg:col-span-2">
                <img
                    v-if="site.logo_horizontal_url || site.logo_url"
                    :src="site.logo_horizontal_url || site.logo_url"
                    :alt="`${siteName} logo`"
                    class="h-10 max-w-52 object-contain"
                />
                <div v-else class="text-xl font-semibold">{{ siteName }}</div>

                <p class="mt-4 max-w-md text-sm leading-6 text-stone-600 dark:text-stone-400">
                    {{ site.tagline || 'Professional digital assets and thoughtful stories for the nudist community.' }}
                </p>

                <div v-if="socialLinks.length" class="mt-6 flex gap-2">
                    <a
                        v-for="item in socialLinks"
                        :key="item.label"
                        :href="item.href"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-stone-300 hover:text-[var(--brand-accent)] dark:border-stone-700"
                        :aria-label="item.label"
                    >
                        <component :is="item.icon" class="h-4 w-4" />
                    </a>
                </div>
            </div>

            <div>
                <h2 class="text-sm font-semibold">Explore</h2>
                <nav class="mt-4 grid gap-3 text-sm text-stone-600 dark:text-stone-400">
                    <Link href="/images" class="hover:text-[var(--brand-accent)]">Marketplace</Link>
                    <Link href="/blog" class="hover:text-[var(--brand-accent)]">Stories</Link>
                    <Link v-for="item in resourcePages" :key="item.href" :href="item.href" class="hover:text-[var(--brand-accent)]">{{ item.label }}</Link>
                    <Link v-if="isAuthenticated" href="/account/wish-lists" class="hover:text-[var(--brand-accent)]">Wish Lists</Link>
                </nav>
            </div>

            <div v-if="companyPages.length">
                <h2 class="text-sm font-semibold">Company</h2>
                <nav class="mt-4 grid gap-3 text-sm text-stone-600 dark:text-stone-400">
                    <Link v-for="item in companyPages" :key="item.href" :href="item.href" class="hover:text-[var(--brand-accent)]">{{ item.label }}</Link>
                </nav>
            </div>

            <div>
                <h2 class="text-sm font-semibold">Account</h2>
                <nav class="mt-4 grid gap-3 text-sm text-stone-600 dark:text-stone-400">
                    <Link v-if="isAuthenticated" :href="dashboard()" class="hover:text-[var(--brand-accent)]">Dashboard</Link>
                    <template v-else>
                        <Link :href="login()" class="hover:text-[var(--brand-accent)]">Log in</Link>
                        <Link :href="register()" class="hover:text-[var(--brand-accent)]">Create account</Link>
                    </template>
                    <a v-if="site.contact_email" :href="`mailto:${site.contact_email}`" class="hover:text-[var(--brand-accent)]">
                        Contact
                    </a>
                </nav>
            </div>
        </div>

        <div class="border-t border-stone-200 dark:border-stone-800">
            <div class="mx-auto flex max-w-[1440px] flex-col gap-2 px-5 py-6 text-xs text-stone-500 sm:px-8 md:flex-row md:justify-between lg:px-12">
                <span>{{ site.footer_text || `© ${siteName}. All rights reserved.` }}</span>
                <nav v-if="legalPages.length" class="flex flex-wrap gap-x-4 gap-y-2"><Link v-for="item in legalPages" :key="item.href" :href="item.href" class="hover:text-[var(--brand-accent)]">{{ item.label }}</Link></nav><span v-else>Authentic media. Thoughtful representation.</span>
            </div>
        </div>
    </footer>
</template>

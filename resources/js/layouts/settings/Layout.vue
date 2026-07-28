<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AccountPageLayout from '@/components/Account/AccountPageLayout.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    { title: 'Profile', href: editProfile() },
    { title: 'Security', href: editSecurity() },
    { title: 'Appearance', href: editAppearance() },
];
const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <AccountPageLayout>
        <template #title>Account Settings</template>
        <template #description>Manage your public profile, sign-in security, and display preferences.</template>

        <div class="flex flex-col gap-8 lg:flex-row lg:gap-12">
            <aside class="w-full lg:w-48">
                <nav class="flex gap-2 overflow-x-auto lg:flex-col" aria-label="Settings">
                    <Button v-for="item in sidebarNavItems" :key="toUrl(item.href)" variant="ghost"
                        :class="['shrink-0 justify-start', { 'bg-stone-200 dark:bg-stone-800': isCurrentOrParentUrl(item.href) }]" as-child>
                        <Link :href="item.href">{{ item.title }}</Link>
                    </Button>
                </nav>
            </aside>
            <Separator class="lg:hidden" />
            <div class="min-w-0 flex-1"><section class="max-w-2xl space-y-12"><slot /></section></div>
        </div>
    </AccountPageLayout>
</template>

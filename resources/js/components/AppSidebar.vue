<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart3, BookOpen, Boxes, Building2, FileText, FolderGit2, ImageIcon, LayoutGrid, LifeBuoy, Megaphone, ShieldCheck } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { useAuth } from '@/composables/useAuth';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const { can } = useAuth();
const authUser = (usePage().props as any).auth?.user;

const mainNavItems: NavItem[] = [
    { title: 'Admin Command Center', href: '/admin', icon: LayoutGrid },
    { title: 'Public Website', href: '/', icon: ImageIcon },
];

if (authUser?.has_advertiser_portal) {
mainNavItems.push({ title: 'Advertiser Portal', href: '/advertiser', icon: Building2 });
}

const adminDashboardItems: NavItem[] = [];

if (can('view_admin')) {
    adminDashboardItems.push(
        { title: 'Assets Dashboard', href: '/admin/assets-dashboard', icon: Boxes },
        { title: 'Blog Dashboard', href: '/admin/blog-dashboard', icon: FileText },
        { title: 'Advertising Dashboard', href: '/admin/advertising-dashboard', icon: Megaphone },
        { title: 'Marketing Dashboard', href: '/admin/marketing-dashboard', icon: BarChart3 },
        ...(can('view_support_tickets') ? [{ title: 'Support Dashboard', href: '/admin/support/dashboard', icon: LifeBuoy }] : []),
        { title: 'Administration Dashboard', href: '/admin/administration-dashboard', icon: ShieldCheck },
    );
}

if (can('view_reports')) {
    adminDashboardItems.push({ title: 'Marketplace Intelligence', href: '/admin/analytics', icon: BarChart3 });
}

const footerNavItems: NavItem[] = [
    { title: 'Repository', href: 'https://github.com/laravel/vue-starter-kit', icon: FolderGit2 },
    { title: 'Documentation', href: 'https://laravel.com/docs/starter-kits#vue', icon: BookOpen },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader><SidebarMenu><SidebarMenuItem><SidebarMenuButton size="lg" as-child><Link :href="dashboard()" aria-label="Go to dashboard"><AppLogo /></Link></SidebarMenuButton></SidebarMenuItem></SidebarMenu></SidebarHeader>
        <SidebarContent>
            <NavMain label="Administration" :items="mainNavItems" />
            <NavMain v-if="adminDashboardItems.length" label="Admin Dashboards" :items="adminDashboardItems" />
        </SidebarContent>
        <SidebarFooter><NavFooter :items="footerNavItems" /><NavUser /></SidebarFooter>
    </Sidebar>
    <slot />
</template>

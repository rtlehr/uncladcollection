<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BadgeDollarSign, Ban, BarChart3, BookOpen, Boxes, Building2, FileText, FolderGit2,
    Heart, ImageIcon, Inbox, LayoutGrid, LifeBuoy, Megaphone, Settings, ShieldCheck, Tags, UserRoundCheck, CircleHelp,
} from '@lucide/vue';
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
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    { title: 'Marketplace', href: '/images', icon: ImageIcon },
    { title: 'Stories', href: '/blog', icon: FileText },
    { title: 'Favorites', href: '/favorites', icon: Heart },
    { title: 'My Library', href: '/purchases', icon: BadgeDollarSign },
    { title: 'Support', href: '/support/tickets', icon: LifeBuoy },
];
if (authUser?.has_advertiser_portal) mainNavItems.push({ title: 'Advertiser Portal', href: '/advertiser', icon: Building2 });

const adminDashboardItems: NavItem[] = [];
if (can('view_admin')) {
    adminDashboardItems.push(
        { title: 'Admin Overview', href: '/admin', icon: LayoutGrid },
        { title: 'Assets Dashboard', href: '/admin/assets-dashboard', icon: Boxes },
        { title: 'Blog Dashboard', href: '/admin/blog-dashboard', icon: FileText },
        { title: 'Advertising Dashboard', href: '/admin/advertising-dashboard', icon: Megaphone },
        { title: 'Marketing Dashboard', href: '/admin/marketing-dashboard', icon: BarChart3 },
        ...(can('view_support_tickets') ? [{ title: 'Support Dashboard', href: '/admin/support/dashboard', icon: LifeBuoy }] : []),
        { title: 'Administration Dashboard', href: '/admin/administration-dashboard', icon: ShieldCheck },
    );
}
if (can('view_reports')) adminDashboardItems.push({ title: 'Marketplace Intelligence', href: '/admin/analytics', icon: BarChart3 });

const assetItems: NavItem[] = [];
if (can('manage_images')) {
    assetItems.push(
        { title: 'Assets', href: '/admin/assets', icon: Boxes },
        { title: 'AI Keyword Exclusions', href: '/admin/ai-keyword-exclusions', icon: Ban },
    );
}
if (can('manage_collections')) assetItems.push({ title: 'Collections', href: '/admin/collections', icon: FolderGit2 });
if (can('manage_orders')) assetItems.push({ title: 'Orders', href: '/admin/orders', icon: BadgeDollarSign });

const blogItems: NavItem[] = [];
if (can('manage_blog_posts')) blogItems.push({ title: 'Blog Posts', href: '/admin/blog-posts', icon: FileText });
if (can('manage_comments')) blogItems.push({ title: 'Comments', href: '/admin/comments', icon: FileText });

const advertisingItems: NavItem[] = [];
if (can('manage_advertisers')) advertisingItems.push({ title: 'Advertisers', href: '/admin/advertisers', icon: Building2 });
if (can('manage_ad_campaigns')) advertisingItems.push({ title: 'Ad Campaigns', href: '/admin/ad-campaigns', icon: Megaphone });
if (can('view_sponsorship_sales')) advertisingItems.push({ title: 'Sponsorship Pipeline', href: '/admin/sponsorship-leads', icon: BadgeDollarSign });
if (can('manage_sponsorship_proposals')) advertisingItems.push({ title: 'Proposals', href: '/admin/sponsorship-proposals', icon: FileText });

const marketingItems: NavItem[] = [];
if (can('manage_site_settings')) {
    marketingItems.push(
        { title: 'Marketing Campaigns', href: '/admin/marketing-campaigns', icon: Megaphone },
        { title: 'Site Settings', href: '/admin/site-settings', icon: Settings },
    );
}

const supportItems: NavItem[] = [];
if (can('view_support_tickets')) supportItems.push(
 { title: 'All Tickets', href: '/admin/support/tickets', icon: LifeBuoy },
 { title: 'My Tickets', href: '/admin/support/tickets?assignee=mine', icon: UserRoundCheck },
 { title: 'Unassigned', href: '/admin/support/tickets?assignee=unassigned', icon: Inbox },
);
if (can('view_support_reports')) supportItems.push({ title: 'Reports', href: '/admin/support/reports', icon: BarChart3 });
if (can('manage_support_categories')) supportItems.push({ title: 'Categories', href: '/admin/support/categories', icon: Tags });

const systemItems: NavItem[] = [];
if (can('manage_users')) systemItems.push({ title: 'Users', href: '/admin/users', icon: ShieldCheck });
if (can('manage_roles')) systemItems.push({ title: 'Roles', href: '/admin/roles', icon: ShieldCheck });
if (can('manage_permissions')) systemItems.push({ title: 'Permissions', href: '/admin/permissions', icon: ShieldCheck });
if (can('manage_categories')) systemItems.push({ title: 'Categories', href: '/admin/categories', icon: FileText });
if (can('manage_tags')) systemItems.push({ title: 'Tags', href: '/admin/tags', icon: FileText });
if (can('view_page_help_admin')) systemItems.push({ title: 'Page Help', href: '/admin/page-help', icon: CircleHelp });

const footerNavItems: NavItem[] = [
    { title: 'Repository', href: 'https://github.com/laravel/vue-starter-kit', icon: FolderGit2 },
    { title: 'Documentation', href: 'https://laravel.com/docs/starter-kits#vue', icon: BookOpen },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader><SidebarMenu><SidebarMenuItem><SidebarMenuButton size="lg" as-child><Link :href="dashboard()" aria-label="Go to dashboard"><AppLogo /></Link></SidebarMenuButton></SidebarMenuItem></SidebarMenu></SidebarHeader>
        <SidebarContent>
            <NavMain label="Main" :items="mainNavItems" />
            <NavMain v-if="adminDashboardItems.length" label="Admin Dashboards" :items="adminDashboardItems" />
            <NavMain v-if="assetItems.length" label="Assets" :items="assetItems" />
            <NavMain v-if="blogItems.length" label="Blog" :items="blogItems" />
            <NavMain v-if="advertisingItems.length" label="Advertising" :items="advertisingItems" />
            <NavMain v-if="marketingItems.length" label="Marketing" :items="marketingItems" />
            <NavMain v-if="supportItems.length" label="Support" :items="supportItems" />
            <NavMain v-if="systemItems.length" label="Administration" :items="systemItems" />
        </SidebarContent>
        <SidebarFooter><NavFooter :items="footerNavItems" /><NavUser /></SidebarFooter>
    </Sidebar>
    <slot />
</template>

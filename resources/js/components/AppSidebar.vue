<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    BadgeDollarSign,
    BookOpen,
    BriefcaseBusiness,
    Building2,
    RectangleHorizontal,
    ClipboardList,
    Download,
    FolderGit2,
    Heart,
    ImageIcon,
    Images,
    KeyRound,
    LayoutGrid,
    LibraryBig,
    Megaphone,
    MessageCircle,
    Newspaper,
    Settings,
    ShieldCheck,
    Tag,
    Tags,
    UsersRound,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useAuth } from '@/composables/useAuth';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const { can } = useAuth();

/**
 * Member-facing navigation
 */
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Marketplace',
        href: '/images',
        icon: ImageIcon,
    },
    {
        title: 'Stories',
        href: '/blog',
        icon: Newspaper,
    },
    {
        title: 'Favorites',
        href: '/favorites',
        icon: Heart,
    },
    {
        title: 'My Library',
        href: '/purchases',
        icon: BadgeDollarSign,
    },
];

const authUser = (usePage().props as any).auth?.user;

if (authUser?.has_advertiser_portal) {
    mainNavItems.push({
        title: 'Advertiser Portal',
        href: '/advertiser',
        icon: Building2,
    });
}

/**
 * Administration overview
 */
const adminOverviewItems: NavItem[] = [];

if (can('view_admin')) {
    adminOverviewItems.push({
        title: 'Admin Dashboard',
        href: '/admin',
        icon: LayoutGrid,
    });
}

if (can('view_reports')) {
    adminOverviewItems.push({
        title: 'Marketplace Intelligence',
        href: '/admin/analytics',
        icon: BarChart3,
    });
}

/**
 * Marketplace content management
 */
const adminContentItems: NavItem[] = [];

if (can('manage_images')) {
    adminContentItems.push({
        title: 'Assets',
        href: '/admin/assets',
        icon: FolderGit2,
    });

    adminContentItems.push({
        title: 'Configuration Library',
        href: '/admin/configuration-templates',
        icon: LibraryBig,
    });

    adminContentItems.push({
        title: 'Legacy Images',
        href: '/admin/images',
        icon: ImageIcon,
    });
}

if (can('manage_collections')) {
    adminContentItems.push({
        title: 'Collections',
        href: '/admin/collections',
        icon: Images,
    });
}

if (can('manage_categories')) {
    adminContentItems.push({
        title: 'Categories',
        href: '/admin/categories',
        icon: Tags,
    });
}

if (can('manage_tags')) {
    adminContentItems.push({
        title: 'Tags',
        href: '/admin/tags',
        icon: Tag,
    });
}

if (can('manage_blog_posts')) {
    adminContentItems.push({
        title: 'Blog Posts',
        href: '/admin/blog-posts',
        icon: Newspaper,
    });
}

if (can('manage_comments')) {
    adminContentItems.push({
        title: 'Comments',
        href: '/admin/comments',
        icon: MessageCircle,
    });
}

/**
 * Sales, licensing, and customer fulfillment
 */
const adminCommerceItems: NavItem[] = [];

if (can('manage_orders')) {
    adminCommerceItems.push({
        title: 'Orders',
        href: '/admin/orders',
        icon: ClipboardList,
    });
}

if (can('manage_licenses')) {
    adminCommerceItems.push({
        title: 'Licenses',
        href: '/admin/licenses',
        icon: KeyRound,
    });
}

if (can('manage_downloads')) {
    adminCommerceItems.push({
        title: 'Downloads',
        href: '/admin/downloads',
        icon: Download,
    });
}

if (can('manage_license_types')) {
    adminCommerceItems.push({
        title: 'License Types',
        href: '/admin/license-types',
        icon: BadgeDollarSign,
    });
}

/**
 * Marketing and site presentation
 */
const adminMarketingItems: NavItem[] = [];

if (can('manage_advertisers')) {
    adminMarketingItems.push({
        title: 'Advertisers',
        href: '/admin/advertisers',
        icon: BriefcaseBusiness,
    });
}

if (can('manage_ad_campaigns')) {
    adminMarketingItems.push({
        title: 'Ad Campaigns',
        href: '/admin/ad-campaigns',
        icon: Megaphone,
    });
}

if (can('manage_ad_placements')) {
    adminMarketingItems.push({
        title: 'Ad Placements',
        href: '/admin/ad-placements',
        icon: RectangleHorizontal,
    });
}


if (can('view_sponsorship_sales')) {
    adminCommerceItems.push({ title: 'Sponsorship Pipeline', href: '/admin/sponsorship-leads', icon: BriefcaseBusiness });
}
if (can('manage_sponsorship_packages')) {
    adminCommerceItems.push({ title: 'Sponsorship Packages', href: '/admin/sponsorship-packages', icon: ClipboardList });
}
if (can('manage_sponsorship_proposals')) {
    adminCommerceItems.push({ title: 'Sponsorship Proposals', href: '/admin/sponsorship-proposals', icon: Newspaper });
}
if (can('manage_ad_inventory')) {
    adminCommerceItems.push({ title: 'Ad Inventory', href: '/admin/ad-inventory', icon: RectangleHorizontal });
}

if (can('view_advertising_billing')) {
    adminMarketingItems.push({
        title: 'Ad Billing',
        href: '/admin/advertising-invoices',
        icon: BadgeDollarSign,
    });
}

if (can('manage_site_settings')) {
    adminMarketingItems.push({
        title: 'Marketing Campaigns',
        href: '/admin/marketing-campaigns',
        icon: Megaphone,
    });

    adminMarketingItems.push({
        title: 'Site Settings',
        href: '/admin/site-settings',
        icon: Settings,
    });
}

/**
 * Users, roles, and security
 */
const adminSystemItems: NavItem[] = [];

if (can('manage_users')) {
    adminSystemItems.push({
        title: 'Users',
        href: '/admin/users',
        icon: UsersRound,
    });
}

if (can('manage_roles')) {
    adminSystemItems.push({
        title: 'Roles',
        href: '/admin/roles',
        icon: ShieldCheck,
    });
}

if (can('manage_permissions')) {
    adminSystemItems.push({
        title: 'Permissions',
        href: '/admin/permissions',
        icon: KeyRound,
    });
}

/**
 * Development resources
 *
 * These may eventually be removed or restricted to local environments.
 */
const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link
                            :href="dashboard()"
                            aria-label="Go to dashboard"
                        >
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain
                label="Main"
                :items="mainNavItems"
            />

            <NavMain
                v-if="adminOverviewItems.length"
                label="Administration"
                :items="adminOverviewItems"
            />

            <NavMain
                v-if="adminContentItems.length"
                label="Content"
                :items="adminContentItems"
            />

            <NavMain
                v-if="adminCommerceItems.length"
                label="Commerce"
                :items="adminCommerceItems"
            />

            <NavMain
                v-if="adminMarketingItems.length"
                label="Marketing"
                :items="adminMarketingItems"
            />

            <NavMain
                v-if="adminSystemItems.length"
                label="System"
                :items="adminSystemItems"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
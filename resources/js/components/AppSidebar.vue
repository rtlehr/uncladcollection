<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    FolderGit2,
    LayoutGrid,
    Settings,
    KeyRound,
    ShieldCheck,
    UsersRound,
    Tags,
    Tag,
    Images,
    ImageIcon,
    Heart,
    BadgeDollarSign,
    ClipboardList,
    Download,
    Newspaper,
    MessageCircle,
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

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Blog',
        href: '/blog',
        icon: Newspaper,
    },
    {
        title: 'Images',
        href: '/images',
        icon: ImageIcon,
    },
    {
        title: 'My Favorites',
        href: '/favorites',
        icon: Heart,
    },
    {
        title: 'My Purchases',
        href: '/purchases',
        icon: BadgeDollarSign,
    },

];

const adminNavItems: NavItem[] = [];

    if (can('manage_site_settings')) {
        adminNavItems.push({
            title: 'Admin Dashboard',
            href: '/admin',
            icon: Settings,
        });
    }

    if (can('manage_site_settings')) {
        adminNavItems.push({
            title: 'Site Settings',
            href: '/admin/site-settings',
            icon: Settings,
        });
    }

    if (can('manage_permissions')) {
        adminNavItems.push({
            title: 'Permissions',
            href: '/admin/permissions',
            icon: KeyRound,
        });

    if (can('manage_roles')) {
        adminNavItems.push({
            title: 'Roles',
            href: '/admin/roles',
            icon: ShieldCheck,
        });
    }

    if (can('manage_users')) {
        adminNavItems.push({
            title: 'Users',
            href: '/admin/users',
            icon: UsersRound,
        });
    }

    if (can('manage_categories')) {
        adminNavItems.push({
            title: 'Categories',
            href: '/admin/categories',
            icon: Tags,
        });
    }

    if (can('manage_tags')) {
        adminNavItems.push({
            title: 'Tags',
            href: '/admin/tags',
            icon: Tag,
        });
    }

    if (can('manage_collections')) {
        adminNavItems.push({
            title: 'Collections',
            href: '/admin/collections',
            icon: Images,
        });
    }

    if (can('manage_images')) {
        adminNavItems.push({
            title: 'Images',
            href: '/admin/images',
            icon: ImageIcon,
        });

    }

    if (can('manage_license_types')) {
        adminNavItems.push({
            title: 'License Types',
            href: '/admin/license-types',
            icon: BadgeDollarSign,
        });
    }

   /* if (can('manage_orders')) {
        adminNavItems.push({
            title: 'Orders',
            href: '/admin/orders',
            icon: ClipboardList,
        });
    }

    if (can('manage_licenses')) {
        adminNavItems.push({
            title: 'Licenses',
            href: '/admin/licenses',
            icon: KeyRound,
        });
    }

    if (can('manage_downloads')) {
        adminNavItems.push({
            title: 'Downloads',
            href: '/admin/downloads',
            icon: Download,
        });
    }*/

    if (can('manage_blog_posts')) {
        adminNavItems.push({
            title: 'Blog Posts',
            href: '/admin/blog-posts',
            icon: Newspaper,
        });
    }

    if (can('manage_comments')) {
        adminNavItems.push({
            title: 'Manage Comments',
            href: '/admin/comments',
            icon: MessageCircle,
        });
    }


}

const showAdminSection =
    can('view_admin') && adminNavItems.length > 0;

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
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <div v-if="showAdminSection" class="mt-4">
                <div class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Admin
                </div>

                <NavMain :items="adminNavItems" />
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
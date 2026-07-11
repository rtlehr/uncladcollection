<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

withDefaults(defineProps<{ items: NavItem[]; label?: string }>(), { label: undefined });
const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-2 py-1">
        <SidebarGroupLabel
            v-if="label"
            class="px-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-sidebar-foreground/55"
        >
            {{ label }}
        </SidebarGroupLabel>

        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                    class="transition-colors"
                >
                    <Link :href="item.href" :aria-current="isCurrentUrl(item.href) ? 'page' : undefined">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>

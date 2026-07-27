<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Activity, BadgeDollarSign, Ban, BarChart3, Boxes, Building2, CalendarRange,
    CircleDollarSign, CircleHelp, Download, FilePlus2, FileSignature, FileText,
    FolderGit2, Gauge, Handshake, Inbox, KeyRound, LifeBuoy, Megaphone,
    MessageSquare, Newspaper, PanelsTopLeft, Search, Settings, Shapes,
    ShieldCheck, ShoppingCart, SlidersHorizontal, Tags, TrendingUp, Upload,
    UserRoundCheck, Users,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import { Input } from '@/components/ui/input';

export type AdminTool = {
    id: string;
    title: string;
    description: string;
    href: string;
    icon: string;
    keywords: string[];
};

export type AdminToolGroup = {
    id: string;
    title: string;
    description: string;
    icon: string;
    tools: AdminTool[];
};

const props = defineProps<{ groups: AdminToolGroup[] }>();

const query = ref('');
const recentIds = ref<string[]>([]);
const storageKey = 'unclad-admin-recent-tools';

const icons: Record<string, any> = {
    Activity, BadgeDollarSign, Ban, BarChart3, Boxes, Building2, CalendarRange,
    CircleDollarSign, CircleHelp, Download, FilePlus2, FileSignature, FileText,
    FolderGit2, Gauge, Handshake, Inbox, KeyRound, LifeBuoy, Megaphone,
    MessageSquare, Newspaper, PanelsTopLeft, Search, Settings, Shapes,
    ShieldCheck, ShoppingCart, SlidersHorizontal, Tags, TrendingUp, Upload,
    UserRoundCheck, Users,
};

const allTools = computed(() => props.groups.flatMap((group) => group.tools));
const normalizedQuery = computed(() => query.value.trim().toLowerCase());

const filteredGroups = computed(() => {
    if (!normalizedQuery.value) return props.groups;

    return props.groups
        .map((group) => ({
            ...group,
            tools: group.tools.filter((tool) =>
                [tool.title, tool.description, group.title, ...(tool.keywords ?? [])]
                    .join(' ')
                    .toLowerCase()
                    .includes(normalizedQuery.value),
            ),
        }))
        .filter((group) => group.tools.length > 0);
});

const recentTools = computed(() =>
    recentIds.value
        .map((id) => allTools.value.find((tool) => tool.id === id))
        .filter((tool): tool is AdminTool => Boolean(tool))
        .slice(0, 6),
);

function iconFor(name: string): any {
    return icons[name] ?? FileText;
}

function remember(tool: AdminTool): void {
    recentIds.value = [tool.id, ...recentIds.value.filter((id) => id !== tool.id)].slice(0, 8);
    window.localStorage.setItem(storageKey, JSON.stringify(recentIds.value));
}

onMounted(() => {
    try {
        const stored = JSON.parse(window.localStorage.getItem(storageKey) ?? '[]');
        if (Array.isArray(stored)) recentIds.value = stored.filter((value) => typeof value === 'string');
    } catch {
        recentIds.value = [];
    }
});
</script>

<template>
    <section class="space-y-6" aria-labelledby="admin-command-center-title">
        <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="border-b bg-muted/30 p-5 sm:p-7">
                <div class="max-w-3xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-primary">Admin Command Center</p>
                    <h2 id="admin-command-center-title" class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">
                        Find every administrative tool in one place
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground sm:text-base">
                        Search by task, subject, or feature. Results only include tools your account is allowed to use.
                    </p>
                </div>

                <div class="relative mt-5 max-w-2xl">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="query"
                        type="search"
                        class="h-12 bg-background pl-11 text-base"
                        placeholder="Search collections, assets, reports, users…"
                        aria-label="Search admin tools"
                        autofocus
                    />
                </div>
            </div>

            <div v-if="recentTools.length && !normalizedQuery" class="border-b p-5 sm:p-7">
                <h3 class="text-sm font-semibold">Recently used</h3>
                <div class="mt-3 flex flex-wrap gap-2">
                    <Link
                        v-for="tool in recentTools"
                        :key="tool.id"
                        :href="tool.href"
                        class="inline-flex items-center gap-2 rounded-full border bg-background px-3 py-2 text-sm font-medium transition hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        @click="remember(tool)"
                    >
                        <component :is="iconFor(tool.icon)" class="h-4 w-4" />
                        {{ tool.title }}
                    </Link>
                </div>
            </div>
        </div>

        <div v-if="filteredGroups.length" class="space-y-8">
            <section v-for="group in filteredGroups" :key="group.id" :aria-labelledby="`admin-group-${group.id}`">
                <div class="mb-3 flex items-start gap-3">
                    <span class="rounded-lg border bg-card p-2 text-primary shadow-sm">
                        <component :is="iconFor(group.icon)" class="h-5 w-5" />
                    </span>
                    <div>
                        <h3 :id="`admin-group-${group.id}`" class="font-semibold">{{ group.title }}</h3>
                        <p class="text-sm text-muted-foreground">{{ group.description }}</p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="tool in group.tools"
                        :key="tool.id"
                        :href="tool.href"
                        class="group flex min-h-28 items-start gap-4 rounded-xl border bg-card p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        @click="remember(tool)"
                    >
                        <span class="rounded-lg bg-muted p-2.5 text-muted-foreground transition group-hover:bg-primary/10 group-hover:text-primary">
                            <component :is="iconFor(tool.icon)" class="h-5 w-5" />
                        </span>
                        <span class="min-w-0">
                            <span class="block font-semibold text-foreground">{{ tool.title }}</span>
                            <span class="mt-1 block text-sm leading-5 text-muted-foreground">{{ tool.description }}</span>
                        </span>
                    </Link>
                </div>
            </section>
        </div>

        <div v-else class="rounded-xl border border-dashed bg-muted/20 px-6 py-12 text-center">
            <Search class="mx-auto h-8 w-8 text-muted-foreground" />
            <h3 class="mt-3 font-semibold">No admin tools found</h3>
            <p class="mt-1 text-sm text-muted-foreground">Try a broader term such as “collection,” “asset,” “user,” or “report.”</p>
        </div>
    </section>
</template>

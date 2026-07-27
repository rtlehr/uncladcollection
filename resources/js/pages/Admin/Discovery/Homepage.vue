<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

type Section = { id: number; section_key: string; label: string; eyebrow: string | null; heading: string | null; description: string | null; sort_order: number; item_limit: number; is_enabled: boolean; audience: string };
const props = defineProps<{ sections: Section[] }>();
function save(section: Section): void { router.patch(`/admin/discovery/homepage/${section.id}`, section, { preserveScroll: true }); }
</script>
<template>
    <Head title="Homepage Discovery" />
    <div class="space-y-6 p-4 md:p-6">
        <div><p class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Marketplace discovery</p><h1 class="text-3xl font-semibold tracking-tight">Homepage discovery composition</h1><p class="mt-2 max-w-3xl text-sm text-muted-foreground">Control the order, audience, headings, item limits, and visibility of each discovery section. Assets are automatically deduplicated across personalized and trending sections.</p></div>
        <div class="space-y-4">
            <article v-for="section in props.sections" :key="section.id" class="rounded-xl border bg-card p-5 shadow-sm">
                <div class="grid gap-4 lg:grid-cols-[1fr_120px_120px_160px_auto] lg:items-end">
                    <div><h2 class="font-semibold">{{ section.label }}</h2><p class="text-xs text-muted-foreground">{{ section.section_key }}</p></div>
                    <label class="grid gap-1 text-xs font-medium">Order<input v-model.number="section.sort_order" type="number" min="0" class="rounded-md border bg-background px-3 py-2 text-sm" /></label>
                    <label class="grid gap-1 text-xs font-medium">Items<input v-model.number="section.item_limit" type="number" min="1" max="12" class="rounded-md border bg-background px-3 py-2 text-sm" /></label>
                    <label class="grid gap-1 text-xs font-medium">Audience<select v-model="section.audience" class="rounded-md border bg-background px-3 py-2 text-sm"><option value="all">Everyone</option><option value="guest">Guests</option><option value="authenticated">Signed-in users</option></select></label>
                    <label class="flex items-center gap-2 text-sm"><input v-model="section.is_enabled" type="checkbox" /> Enabled</label>
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="grid gap-1 text-xs font-medium">Eyebrow<input v-model="section.eyebrow" class="rounded-md border bg-background px-3 py-2 text-sm" /></label>
                    <label class="grid gap-1 text-xs font-medium">Heading<input v-model="section.heading" class="rounded-md border bg-background px-3 py-2 text-sm" /></label>
                    <label class="grid gap-1 text-xs font-medium md:col-span-2">Description<textarea v-model="section.description" rows="2" class="rounded-md border bg-background px-3 py-2 text-sm" /></label>
                </div>
                <div class="mt-4 text-right"><button type="button" class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground" @click="save(section)">Save section</button></div>
            </article>
        </div>
    </div>
</template>

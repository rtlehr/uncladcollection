<script setup lang="ts">
import { ImageIcon, Monitor, Moon, Smartphone, Sun, Upload, X } from '@lucide/vue';
import { computed, onBeforeUnmount, reactive, watch } from 'vue';
import type { SiteSetting } from '@/types/siteSetting';

const props = defineProps<{
    settings: SiteSetting[];
    values: Record<number, string>;
    uploads: Record<string, File | null>;
    removals: Record<string, boolean>;
    errors: Record<string, string | undefined>;
}>();

const emit = defineEmits<{
    'update:value': [id: number, value: string];
    'update:upload': [key: string, file: File | null];
    'update:remove': [key: string, value: boolean];
}>();

const assets = [
    { key: 'logo_full', title: 'Full Logo', help: 'Stacked logo for landing pages and spacious layouts.', size: 'SVG preferred · PNG/WebP up to 4 MB', icon: Monitor },
    { key: 'logo_horizontal', title: 'Horizontal Logo', help: 'Primary desktop header logo. Falls back to Full Logo.', size: 'SVG preferred · about 600 px wide', icon: Monitor },
    { key: 'logo_icon', title: 'Icon Logo', help: 'Graphic-only mark for compact and square placements.', size: 'SVG preferred · square artwork', icon: Smartphone },
    { key: 'logo_light', title: 'Light Logo', help: 'White or pale treatment for dark backgrounds.', size: 'SVG preferred · transparent', icon: Moon },
    { key: 'logo_dark', title: 'Dark Logo', help: 'Dark treatment for light backgrounds.', size: 'SVG preferred · transparent', icon: Sun },
    { key: 'watermark_logo', title: 'Watermark Logo', help: 'Transparent graphic used on protected previews.', size: 'PNG/WebP · about 1000 × 1000', icon: ImageIcon },
    { key: 'social_image', title: 'Default Social Image', help: 'Fallback card used when shared pages have no image.', size: '1200 × 630 · JPG/PNG/WebP', icon: ImageIcon },
    { key: 'email_logo', title: 'Email Logo', help: 'Email-safe logo. Falls back to Horizontal Logo.', size: 'PNG/WebP · about 600 px wide', icon: ImageIcon },
    { key: 'app_icon', title: 'App & Browser Icon', help: 'Square master used to generate favicon and device icons.', size: '1024 × 1024 PNG recommended', icon: Smartphone },
];

const previewUrls = reactive<Record<string, string>>({});
const byKey = computed(() => new Map(props.settings.map((setting) => [setting.setting_key, setting])));
const controls = computed(() => props.settings.filter((setting) => setting.setting_key.startsWith('watermark_') && setting.setting_key !== 'watermark_logo'));

function setting(key: string): SiteSetting | undefined {
    return byKey.value.get(key);
}

function current(key: string): string {
    const item = setting(key);
    return item ? props.values[item.id] || '' : '';
}

function revokePreview(key: string): void {
    if (!previewUrls[key]) {
        return;
    }

    URL.revokeObjectURL(previewUrls[key]);
    delete previewUrls[key];
}

function choose(key: string, event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    revokePreview(key);

    if (file) {
        previewUrls[key] = URL.createObjectURL(file);
        emit('update:remove', key, false);
    }

    emit('update:upload', key, file);
}

function remove(key: string): void {
    revokePreview(key);
    emit('update:upload', key, null);
    emit('update:remove', key, true);
}

function label(key: string): string {
    return key.replaceAll('_', ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

watch(
    () => props.uploads,
    (uploads) => {
        for (const asset of assets) {
            if (!uploads[asset.key]) {
                revokePreview(asset.key);
            }
        }
    },
    { deep: true },
);

onBeforeUnmount(() => {
    Object.keys(previewUrls).forEach(revokePreview);
});
</script>

<template>
    <div class="space-y-8">
        <section>
            <div class="mb-4">
                <h3 class="text-lg font-semibold">Brand assets</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Upload each master once. The site uses sensible fallbacks when an optional treatment is not supplied.
                </p>
            </div>

            <div class="grid gap-5 xl:grid-cols-2">
                <article
                    v-for="asset in assets"
                    :key="asset.key"
                    class="overflow-hidden rounded-xl border bg-background"
                >
                    <div class="flex gap-4 p-5">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-muted">
                            <component :is="asset.icon" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-semibold">{{ asset.title }}</h4>
                            <p class="mt-1 text-sm leading-5 text-muted-foreground">{{ asset.help }}</p>
                            <p class="mt-2 text-xs font-medium text-muted-foreground">{{ asset.size }}</p>
                        </div>
                    </div>

                    <div class="border-y bg-[linear-gradient(45deg,#f3f4f6_25%,transparent_25%),linear-gradient(-45deg,#f3f4f6_25%,transparent_25%),linear-gradient(45deg,transparent_75%,#f3f4f6_75%),linear-gradient(-45deg,transparent_75%,#f3f4f6_75%)] bg-[length:18px_18px] bg-[position:0_0,0_9px,9px_-9px,-9px_0px] p-4 dark:bg-none dark:bg-muted/30">
                        <div class="flex h-36 items-center justify-center rounded-lg bg-background/90 p-4">
                            <img
                                v-if="previewUrls[asset.key]"
                                :src="previewUrls[asset.key]"
                                :alt="`${asset.title} preview`"
                                class="max-h-full max-w-full object-contain"
                            />
                            <img
                                v-else-if="!removals[asset.key] && current(asset.key)"
                                :src="current(asset.key)"
                                :alt="asset.title"
                                class="max-h-full max-w-full object-contain"
                            />
                            <div v-else class="text-center text-sm text-muted-foreground">
                                <ImageIcon class="mx-auto mb-2 h-7 w-7" />
                                No image uploaded
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 p-4">
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium hover:bg-muted">
                            <Upload class="h-4 w-4" />
                            Choose file
                            <input
                                type="file"
                                class="sr-only"
                                :accept="asset.key === 'watermark_logo'
                                    ? '.png,.webp,image/png,image/webp'
                                    : asset.key === 'app_icon' || asset.key === 'social_image' || asset.key === 'email_logo'
                                        ? '.png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp'
                                        : '.svg,.png,.webp,image/svg+xml,image/png,image/webp'"
                                @change="choose(asset.key, $event)"
                            />
                        </label>

                        <button
                            v-if="(!removals[asset.key] && current(asset.key)) || uploads[asset.key]"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm text-destructive hover:bg-destructive/10"
                            @click="remove(asset.key)"
                        >
                            <X class="h-4 w-4" />
                            Remove
                        </button>

                        <p v-if="uploads[asset.key]" class="min-w-0 flex-1 truncate text-sm text-muted-foreground">
                            {{ uploads[asset.key]?.name }}
                        </p>

                        <p v-if="errors[asset.key]" class="w-full text-sm text-destructive">
                            {{ errors[asset.key] }}
                        </p>
                    </div>
                </article>
            </div>
        </section>

        <section class="rounded-xl border p-5">
            <h3 class="text-lg font-semibold">Watermark behavior</h3>
            <p class="mt-1 text-sm text-muted-foreground">
                These controls are stored now and can be consumed by the preview-generation service.
            </p>

            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <label v-for="control in controls" :key="control.id" class="space-y-2">
                    <span class="text-sm font-medium">{{ label(control.setting_key) }}</span>
                    <select
                        v-if="control.setting_key === 'watermark_position'"
                        :value="values[control.id]"
                        class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                        @change="emit('update:value', control.id, ($event.target as HTMLSelectElement).value)"
                    >
                        <option value="center">Center</option>
                        <option value="top-left">Top left</option>
                        <option value="top-right">Top right</option>
                        <option value="bottom-left">Bottom left</option>
                        <option value="bottom-right">Bottom right</option>
                    </select>
                    <input
                        v-else-if="control.setting_type === 'boolean'"
                        type="checkbox"
                        :checked="values[control.id] === 'true'"
                        class="h-5 w-5"
                        @change="emit('update:value', control.id, ($event.target as HTMLInputElement).checked ? 'true' : 'false')"
                    />
                    <input
                        v-else
                        type="number"
                        :min="control.setting_key === 'watermark_opacity' ? 10 : 0"
                        :max="control.setting_key === 'watermark_margin' ? 200 : 100"
                        :value="values[control.id]"
                        class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                        @input="emit('update:value', control.id, ($event.target as HTMLInputElement).value)"
                    />
                </label>
            </div>
        </section>
    </div>
</template>

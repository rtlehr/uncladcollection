<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, ImagePlus, Pencil, Plus, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';
import AccountPageLayout from '@/components/Account/AccountPageLayout.vue';
import { Button } from '@/components/ui/button';
import { appConfirm } from '@/lib/appDialog';

interface ExportRecord {
    uuid: string;
    width: number;
    height: number;
    format: string;
    preset_name: string | null;
    size: string | null;
    created_at: string;
    download_url: string;
    delete_url: string;
}

interface Project {
    uuid: string;
    title: string;
    status: string;
    updated_at: string;
    canvas: [number, number];
    preview_url: string | null;
    edit_url: string;
    export_count: number;
    latest_exports: ExportRecord[];
}

type CanvasPreset = {
    name: string;
    width: number;
    height: number;
};

defineProps<{ projects: Project[] }>();

const presets: CanvasPreset[] = [
    { name: 'Social Square', width: 1080, height: 1080 },
    { name: 'Social Portrait', width: 1080, height: 1350 },
    { name: 'Story / Reel', width: 1080, height: 1920 },
    { name: 'HD Landscape', width: 1920, height: 1080 },
];

const createOpen = ref(false);
const creating = ref(false);
const createError = ref('');
const newTitle = ref('Untitled Design');
const newWidth = ref(1920);
const newHeight = ref(1080);

async function remove(project: Project): Promise<void> {
    if (await appConfirm(`Delete “${project.title}”?`, { title: 'Delete design?', confirmLabel: 'Delete Design', destructive: true })) {
        router.delete(`/account/designs/${project.uuid}`);
    }
}

function openCreate(): void {
    newTitle.value = 'Untitled Design';
    newWidth.value = 1920;
    newHeight.value = 1080;
    createError.value = '';
    createOpen.value = true;
}

function applyPreset(preset: CanvasPreset): void {
    newWidth.value = preset.width;
    newHeight.value = preset.height;
}

function createBlankDesign(): void {
    const width = Math.round(Number(newWidth.value));
    const height = Math.round(Number(newHeight.value));

    createError.value = '';

    if (!Number.isFinite(width) || !Number.isFinite(height) || width < 320 || height < 320) {
        createError.value = 'Canvas dimensions must be at least 320×320 pixels.';

        return;
    }

    creating.value = true;
    router.post('/account/designs', {
        title: newTitle.value,
        canvas_width: width,
        canvas_height: height,
    }, {
        onError: errors => {
            createError.value = Object.values(errors)[0] ?? 'The blank design could not be created.';
        },
        onFinish: () => {
            creating.value = false;
        },
    });
}
</script>

<template>
    <Head title="My Designs" />

    <AccountPageLayout>
        <template #title>My Designs</template>
        <template #description>Create a design from a blank canvas or personalize a licensed image. Your original purchases remain unchanged.</template>

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-stone-500">Start with a clean workspace, or use Customize Image from My Library.</p>
            <Button @click="openCreate">
                <Plus class="mr-2 h-4 w-4" />
                New Design
            </Button>
        </div>

        <div v-if="projects.length" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="project in projects"
                :key="project.uuid"
                class="overflow-hidden rounded-3xl border border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-900"
            >
                <div class="aspect-video bg-white dark:bg-stone-800">
                    <img
                        v-if="project.preview_url"
                        :src="project.preview_url"
                        :alt="project.title"
                        class="h-full w-full object-cover"
                    />
                    <div v-else class="flex h-full items-center justify-center border-b border-stone-100 bg-white dark:border-stone-800">
                        <ImagePlus class="h-10 w-10 text-stone-300" />
                    </div>
                </div>

                <div class="p-5">
                    <h2 class="font-semibold">{{ project.title }}</h2>
                    <p class="mt-1 text-sm text-stone-500">
                        {{ project.canvas[0] }} × {{ project.canvas[1] }} · Updated {{ project.updated_at }}
                    </p>
                    <p class="mt-2 text-xs text-stone-500">
                        {{ project.export_count }} saved export{{ project.export_count === 1 ? '' : 's' }}
                    </p>

                    <div v-if="project.latest_exports.length" class="mt-3 space-y-1">
                        <a
                            v-for="record in project.latest_exports"
                            :key="record.uuid"
                            :href="record.download_url"
                            class="flex items-center justify-between rounded-lg bg-stone-50 px-3 py-2 text-xs hover:bg-stone-100 dark:bg-stone-800 dark:hover:bg-stone-700"
                        >
                            <span class="flex items-center gap-2">
                                <Download class="h-3.5 w-3.5" />
                                {{ record.preset_name || 'Custom' }}
                            </span>
                            <span class="text-stone-500">{{ record.width }}×{{ record.height }}</span>
                        </a>
                    </div>

                    <div class="mt-5 flex gap-2">
                        <Button as-child class="flex-1">
                            <Link :href="project.edit_url">
                                <Pencil class="mr-2 h-4 w-4" />
                                Edit design
                            </Link>
                        </Button>
                        <Button variant="outline" size="icon" aria-label="Delete design" @click="remove(project)">
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </article>
        </div>

        <div v-else class="rounded-3xl border border-dashed p-12 text-center">
            <ImagePlus class="mx-auto h-12 w-12 text-stone-400" />
            <h2 class="mt-4 text-xl font-semibold">No designs yet</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-stone-500">
                Start with a blank white canvas, then add text, shapes, uploads, or licensed images from My Library.
            </p>
            <Button class="mt-6" @click="openCreate">
                <Plus class="mr-2 h-4 w-4" />
                Create New Design
            </Button>
        </div>

        <div
            v-if="createOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
            @click.self="createOpen = false"
        >
            <div class="w-full max-w-xl rounded-2xl border border-stone-200 bg-white shadow-2xl dark:border-stone-800 dark:bg-stone-950">
                <div class="flex items-center justify-between border-b border-stone-200 p-5 dark:border-stone-800">
                    <div>
                        <h2 class="text-lg font-semibold">Create New Design</h2>
                        <p class="mt-1 text-sm text-stone-500">Choose the starting size for your blank white canvas.</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-stone-500 hover:bg-stone-100 dark:hover:bg-stone-900"
                        aria-label="Close"
                        @click="createOpen = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form class="space-y-5 p-5" @submit.prevent="createBlankDesign">
                    <label class="block text-sm font-medium">
                        Design name
                        <input
                            v-model="newTitle"
                            type="text"
                            maxlength="120"
                            class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm dark:border-stone-700 dark:bg-stone-900"
                        />
                    </label>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Workspace presets</p>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <Button
                                v-for="preset in presets"
                                :key="preset.name"
                                type="button"
                                variant="secondary"
                                class="justify-between"
                                @click="applyPreset(preset)"
                            >
                                <span>{{ preset.name }}</span>
                                <span class="text-xs text-stone-500">{{ preset.width }}×{{ preset.height }}</span>
                            </Button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="text-sm font-medium">
                            Width
                            <input
                                v-model.number="newWidth"
                                type="number"
                                min="320"
                                max="12000"
                                class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm dark:border-stone-700 dark:bg-stone-900"
                            />
                        </label>
                        <label class="text-sm font-medium">
                            Height
                            <input
                                v-model.number="newHeight"
                                type="number"
                                min="320"
                                max="12000"
                                class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm dark:border-stone-700 dark:bg-stone-900"
                            />
                        </label>
                    </div>

                    <p
                        v-if="createError"
                        class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
                    >
                        {{ createError }}
                    </p>

                    <div class="flex justify-end gap-2 border-t border-stone-200 pt-5 dark:border-stone-800">
                        <Button type="button" variant="secondary" @click="createOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="creating">
                            {{ creating ? 'Creating…' : 'Create Design' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AccountPageLayout>
</template>

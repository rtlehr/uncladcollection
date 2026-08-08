<script setup lang="ts">
import {
    ExternalLink,
    ImageIcon,
    Maximize2,
    Monitor,
    Smartphone,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

export type BlogMediaProperties = {
    alt: string;
    caption: string;
    credit: string;
    photographer: string;
    assetTitle: string;
    publicUrl: string;
    showLicenseLink: boolean;
    clickToEnlarge: boolean;
    borderStyle: 'none' | 'thin' | 'card';
    shadowStyle: 'none' | 'soft' | 'strong';
    roundedStyle: 'none' | 'small' | 'large';
    spacingStyle: 'tight' | 'normal' | 'large';
};

const props = defineProps<{
    open: boolean;
    imageSrc: string | null;
    initial: BlogMediaProperties;
    hasAsset: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    apply: [value: BlogMediaProperties];
}>();

const form = reactive<BlogMediaProperties>({
    alt: '',
    caption: '',
    credit: '',
    photographer: '',
    assetTitle: '',
    publicUrl: '',
    showLicenseLink: false,
    clickToEnlarge: false,
    borderStyle: 'none',
    shadowStyle: 'none',
    roundedStyle: 'small',
    spacingStyle: 'normal',
});

const previewMode = ref<'desktop' | 'mobile'>('desktop');

watch(
    () => props.open,
    (value) => {
        if (!value) {
return;
}

        Object.assign(form, props.initial);
        previewMode.value = 'desktop';
    },
);

const canShowLicense = computed(
    () => props.hasAsset && form.publicUrl.trim() !== '',
);

const effectiveCredit = computed(() => {
    if (form.credit.trim()) {
        return form.credit.trim();
    }

    if (form.photographer.trim()) {
        return `Photography by ${form.photographer.trim()}`;
    }

    return '';
});

const showCaptionArea = computed(
    () =>
        form.caption.trim() !== ''
        || effectiveCredit.value !== ''
        || (form.showLicenseLink && canShowLicense.value),
);

const previewShellClasses = computed(() => ({
    'border border-border': form.borderStyle === 'thin',
    'border border-border bg-card p-2': form.borderStyle === 'card',
    'shadow-md': form.shadowStyle === 'soft',
    'shadow-2xl': form.shadowStyle === 'strong',
    'rounded-none': form.roundedStyle === 'none',
    'rounded-lg': form.roundedStyle === 'small',
    'rounded-2xl': form.roundedStyle === 'large',
    'my-2': form.spacingStyle === 'tight',
    'my-6': form.spacingStyle === 'normal',
    'my-10': form.spacingStyle === 'large',
}));

const previewImageClasses = computed(() => ({
    'rounded-none': form.roundedStyle === 'none',
    'rounded-md': form.roundedStyle === 'small',
    'rounded-xl': form.roundedStyle === 'large',
    'cursor-zoom-in': form.clickToEnlarge,
}));

function apply(): void {
    emit('apply', { ...form });
    emit('update:open', false);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="max-h-[94vh] w-[97vw] max-w-6xl overflow-y-auto sm:max-w-6xl"
        >
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <ImageIcon class="h-5 w-5" />
                    Image Details
                </DialogTitle>
                <DialogDescription>
                    Changes update the preview immediately. Apply them when the
                    image looks right.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-6 xl:grid-cols-[minmax(360px,0.95fr)_minmax(420px,1.05fr)]">
                <aside class="xl:sticky xl:top-0 xl:self-start">
                    <div
                        class="overflow-hidden rounded-2xl border bg-muted/15"
                    >
                        <div
                            class="flex items-center justify-between gap-3 border-b bg-background px-4 py-3"
                        >
                            <div>
                                <p class="text-sm font-semibold">
                                    Live Article Preview
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Approximate public article appearance
                                </p>
                            </div>

                            <div
                                class="flex rounded-lg border bg-muted/30 p-1"
                            >
                                <Button
                                    type="button"
                                    size="sm"
                                    :variant="
                                        previewMode === 'desktop'
                                            ? 'secondary'
                                            : 'ghost'
                                    "
                                    class="h-8 px-2.5"
                                    title="Desktop preview"
                                    @click="previewMode = 'desktop'"
                                >
                                    <Monitor class="h-4 w-4" />
                                </Button>

                                <Button
                                    type="button"
                                    size="sm"
                                    :variant="
                                        previewMode === 'mobile'
                                            ? 'secondary'
                                            : 'ghost'
                                    "
                                    class="h-8 px-2.5"
                                    title="Mobile preview"
                                    @click="previewMode = 'mobile'"
                                >
                                    <Smartphone class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <div class="overflow-x-auto p-4 sm:p-6">
                            <div
                                class="mx-auto transition-all duration-200"
                                :class="
                                    previewMode === 'mobile'
                                        ? 'max-w-[320px]'
                                        : 'max-w-[560px]'
                                "
                            >
                                <div
                                    class="overflow-hidden transition-all"
                                    :class="previewShellClasses"
                                >
                                    <div
                                        class="relative overflow-hidden"
                                        :class="previewImageClasses"
                                    >
                                        <img
                                            v-if="imageSrc"
                                            :src="imageSrc"
                                            :alt="form.alt"
                                            class="mx-auto block h-auto max-h-[420px] max-w-full object-contain"
                                            :class="previewImageClasses"
                                        />

                                        <div
                                            v-else
                                            class="flex aspect-video items-center justify-center bg-muted text-muted-foreground"
                                        >
                                            <ImageIcon class="h-10 w-10" />
                                        </div>

                                        <div
                                            v-if="form.clickToEnlarge"
                                            class="pointer-events-none absolute right-2 top-2 rounded-full bg-black/65 p-2 text-white shadow"
                                            title="Click to enlarge enabled"
                                        >
                                            <Maximize2 class="h-4 w-4" />
                                        </div>
                                    </div>

                                    <div
                                        v-if="showCaptionArea"
                                        class="flex flex-wrap items-center gap-x-4 gap-y-1 border-t bg-muted/25 px-3 py-2.5 text-xs"
                                    >
                                        <p
                                            v-if="form.caption"
                                            class="w-full leading-5 text-foreground"
                                        >
                                            {{ form.caption }}
                                        </p>

                                        <span
                                            v-if="effectiveCredit"
                                            class="text-muted-foreground"
                                        >
                                            {{ effectiveCredit }}
                                        </span>

                                        <a
                                            v-if="
                                                form.showLicenseLink
                                                && canShowLicense
                                            "
                                            :href="form.publicUrl"
                                            class="ml-auto inline-flex items-center gap-1 font-semibold text-primary"
                                            @click.prevent
                                        >
                                            License This Image
                                            <ExternalLink class="h-3 w-3" />
                                        </a>
                                    </div>
                                </div>

                                <p
                                    v-if="form.alt"
                                    class="mt-3 text-xs text-muted-foreground"
                                >
                                    <strong>Alt:</strong> {{ form.alt }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <p class="mt-3 text-xs leading-5 text-muted-foreground">
                        Image dimensions and left/center/right placement remain
                        controlled by the Image Tools toolbar.
                    </p>
                </aside>

                <div class="space-y-6">
                    <section class="space-y-4 rounded-xl border p-4">
                        <div>
                            <h3 class="font-semibold">Accessibility</h3>
                            <p class="text-sm text-muted-foreground">
                                Alt text should briefly describe what is visible.
                            </p>
                        </div>

                        <label class="block text-sm">
                            <span class="mb-1 block font-medium">Alt text</span>
                            <input
                                v-model="form.alt"
                                class="h-10 w-full rounded-md border bg-background px-3"
                                maxlength="500"
                                placeholder="Describe the image"
                            />
                        </label>

                        <label class="block text-sm">
                            <span class="mb-1 block font-medium">Caption</span>
                            <textarea
                                v-model="form.caption"
                                class="min-h-20 w-full rounded-md border bg-background px-3 py-2"
                                maxlength="1000"
                                placeholder="Optional caption shown below the image"
                            />
                        </label>
                    </section>

                    <section class="space-y-4 rounded-xl border p-4">
                        <div>
                            <h3 class="font-semibold">Credit and Asset</h3>
                            <p class="text-sm text-muted-foreground">
                                Asset Library images are prefilled automatically.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm">
                                <span class="mb-1 block font-medium">
                                    Photographer
                                </span>
                                <input
                                    v-model="form.photographer"
                                    class="h-10 w-full rounded-md border bg-background px-3"
                                    maxlength="255"
                                />
                            </label>

                            <label class="block text-sm">
                                <span class="mb-1 block font-medium">
                                    Credit line
                                </span>
                                <input
                                    v-model="form.credit"
                                    class="h-10 w-full rounded-md border bg-background px-3"
                                    maxlength="500"
                                    placeholder="Photo courtesy of..."
                                />
                            </label>
                        </div>

                        <label class="block text-sm">
                            <span class="mb-1 block font-medium">
                                Asset title
                            </span>
                            <input
                                v-model="form.assetTitle"
                                class="h-10 w-full rounded-md border bg-background px-3"
                                maxlength="255"
                            />
                        </label>

                        <label class="block text-sm">
                            <span class="mb-1 block font-medium">
                                Marketplace URL
                            </span>
                            <input
                                v-model="form.publicUrl"
                                type="url"
                                class="h-10 w-full rounded-md border bg-background px-3"
                                placeholder="https://..."
                            />
                        </label>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="form.showLicenseLink"
                                    type="checkbox"
                                    :disabled="!canShowLicense"
                                />
                                Show “License This Image”
                            </label>

                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="form.clickToEnlarge"
                                    type="checkbox"
                                />
                                Click image to enlarge
                            </label>
                        </div>
                    </section>

                    <section class="space-y-4 rounded-xl border p-4">
                        <div>
                            <h3 class="font-semibold">Appearance</h3>
                            <p class="text-sm text-muted-foreground">
                                These choices are reflected instantly in the
                                preview.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm">
                                <span class="mb-1 block font-medium">Border</span>
                                <select
                                    v-model="form.borderStyle"
                                    class="h-10 w-full rounded-md border bg-background px-3"
                                >
                                    <option value="none">None</option>
                                    <option value="thin">Thin</option>
                                    <option value="card">Card</option>
                                </select>
                            </label>

                            <label class="block text-sm">
                                <span class="mb-1 block font-medium">Shadow</span>
                                <select
                                    v-model="form.shadowStyle"
                                    class="h-10 w-full rounded-md border bg-background px-3"
                                >
                                    <option value="none">None</option>
                                    <option value="soft">Soft</option>
                                    <option value="strong">Strong</option>
                                </select>
                            </label>

                            <label class="block text-sm">
                                <span class="mb-1 block font-medium">
                                    Rounded corners
                                </span>
                                <select
                                    v-model="form.roundedStyle"
                                    class="h-10 w-full rounded-md border bg-background px-3"
                                >
                                    <option value="none">None</option>
                                    <option value="small">Small</option>
                                    <option value="large">Large</option>
                                </select>
                            </label>

                            <label class="block text-sm">
                                <span class="mb-1 block font-medium">
                                    Spacing
                                </span>
                                <select
                                    v-model="form.spacingStyle"
                                    class="h-10 w-full rounded-md border bg-background px-3"
                                >
                                    <option value="tight">Tight</option>
                                    <option value="normal">Normal</option>
                                    <option value="large">Large</option>
                                </select>
                            </label>
                        </div>
                    </section>
                </div>
            </div>

            <DialogFooter class="sticky bottom-0 border-t bg-background pt-4">
                <Button
                    type="button"
                    variant="outline"
                    @click="emit('update:open', false)"
                >
                    Cancel
                </Button>
                <Button type="button" @click="apply">
                    Apply Properties
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

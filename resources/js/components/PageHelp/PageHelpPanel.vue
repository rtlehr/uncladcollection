<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ExternalLink, GripVertical, HelpCircle, X } from '@lucide/vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

type HelpEntry = {
    id: number;
    title: string;
    summary?: string | null;
    content: string;
};

type PageHelpPayload = {
    key: string;
    page_name: string;
    entries: HelpEntry[];
    can_manage: boolean;
    manage_url?: string | null;
};

const props = withDefaults(defineProps<{
    help: PageHelpPayload;
    publicStyle?: boolean;
}>(), {
    publicStyle: false,
});

const STORAGE_OPEN = 'unclad.page-help.open';
const STORAGE_WIDTH = 'unclad.page-help.width';
const MIN_WIDTH = 320;
const MAX_WIDTH = 640;
const DEFAULT_WIDTH = 420;

const isMounted = ref(false);
const isOpen = ref(false);
const panelWidth = ref(DEFAULT_WIDTH);
const isDesktop = ref(false);
const panelHeading = ref<HTMLElement | null>(null);
let mediaQuery: MediaQueryList | null = null;
let startX = 0;
let startWidth = DEFAULT_WIDTH;

const panelStyle = computed(() => ({
    width: isDesktop.value ? `${panelWidth.value}px` : '100%',
}));

function clampWidth(width: number): number {
    return Math.min(MAX_WIDTH, Math.max(MIN_WIDTH, width));
}

function syncWorkspaceClass(): void {
    if (typeof document === 'undefined') return;

    const desktopOpen = isOpen.value && isDesktop.value;
    document.body.classList.toggle('page-help-workspace-open', desktopOpen);
    document.body.style.setProperty('--page-help-workspace-width', `${panelWidth.value}px`);
}

function setOpen(open: boolean): void {
    isOpen.value = open;
    localStorage.setItem(STORAGE_OPEN, open ? 'true' : 'false');
    window.dispatchEvent(new CustomEvent('page-help-open-change', { detail: open }));

    if (open) {
        nextTick(() => panelHeading.value?.focus({ preventScroll: true }));
    }
}

function toggle(): void {
    setOpen(!isOpen.value);
}

function close(): void {
    setOpen(false);
}

function onOpenChange(event: Event): void {
    const customEvent = event as CustomEvent<boolean>;
    isOpen.value = Boolean(customEvent.detail);
}

function onMediaChange(event: MediaQueryListEvent | MediaQueryList): void {
    isDesktop.value = event.matches;
    syncWorkspaceClass();
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape' && isOpen.value) close();
}

function startResize(event: PointerEvent): void {
    if (!isDesktop.value) return;

    startX = event.clientX;
    startWidth = panelWidth.value;
    window.addEventListener('pointermove', resize);
    window.addEventListener('pointerup', stopResize, { once: true });
    document.body.classList.add('page-help-workspace-resizing');
}

function resize(event: PointerEvent): void {
    panelWidth.value = clampWidth(startWidth + (startX - event.clientX));
}

function stopResize(): void {
    window.removeEventListener('pointermove', resize);
    document.body.classList.remove('page-help-workspace-resizing');
    localStorage.setItem(STORAGE_WIDTH, String(panelWidth.value));
}

watch([isOpen, panelWidth, isDesktop], syncWorkspaceClass);

onMounted(() => {
    isMounted.value = true;
    const savedWidth = Number(localStorage.getItem(STORAGE_WIDTH));
    if (Number.isFinite(savedWidth) && savedWidth > 0) {
        panelWidth.value = clampWidth(savedWidth);
    }

    isOpen.value = localStorage.getItem(STORAGE_OPEN) === 'true';
    mediaQuery = window.matchMedia('(min-width: 1024px)');
    onMediaChange(mediaQuery);
    mediaQuery.addEventListener('change', onMediaChange);
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('page-help-open-change', onOpenChange);
    syncWorkspaceClass();
});

onBeforeUnmount(() => {
    mediaQuery?.removeEventListener('change', onMediaChange);
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('pointermove', resize);
    window.removeEventListener('page-help-open-change', onOpenChange);
    document.body.classList.remove('page-help-workspace-open', 'page-help-workspace-resizing');
});
</script>

<template>
    <TooltipProvider :delay-duration="200">
        <Tooltip>
            <TooltipTrigger as-child>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    :class="publicStyle
                        ? 'h-10 w-10 rounded-full border border-stone-300 dark:border-stone-700'
                        : 'h-9 w-9'"
                    :aria-label="`${isOpen ? 'Close' : 'Open'} help for ${help.page_name}`"
                    :aria-expanded="isOpen"
                    aria-controls="page-help-workspace"
                    @click="toggle"
                >
                    <HelpCircle class="h-5 w-5" aria-hidden="true" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ isOpen ? 'Close page help' : 'Help for this page' }}</TooltipContent>
        </Tooltip>
    </TooltipProvider>

    <Teleport v-if="isMounted" to="body">
        <button
            v-if="isOpen && !isDesktop"
            type="button"
            class="fixed inset-0 z-[79] bg-black/40 lg:hidden"
            aria-label="Close page help"
            @click="close"
        />

        <aside
            v-if="isOpen"
            id="page-help-workspace"
            :style="panelStyle"
            class="fixed inset-y-0 right-0 z-[80] flex max-w-full flex-col border-l bg-background shadow-2xl"
            aria-label="Page help workspace"
        >
            <button
                v-if="isDesktop"
                type="button"
                class="absolute inset-y-0 left-0 flex w-3 -translate-x-1/2 cursor-col-resize items-center justify-center text-muted-foreground hover:text-foreground"
                aria-label="Resize page help panel"
                @pointerdown.prevent="startResize"
            >
                <GripVertical class="h-5 w-5" aria-hidden="true" />
            </button>

            <header class="flex items-start justify-between gap-4 border-b px-6 py-5">
                <div>
                    <h2
                        ref="panelHeading"
                        tabindex="-1"
                        class="text-lg font-semibold outline-none"
                    >
                        {{ help.page_name }}
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Keep this panel open while you work on the page.
                    </p>
                </div>

                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="h-9 w-9 shrink-0"
                    aria-label="Close page help"
                    @click="close"
                >
                    <X class="h-5 w-5" aria-hidden="true" />
                </Button>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">
                <div class="space-y-5">
                    <template v-if="help.entries.length">
                        <article
                            v-for="entry in help.entries"
                            :key="entry.id"
                            class="rounded-xl border bg-card p-5 shadow-sm"
                        >
                            <div>
                                <h3 class="text-lg font-semibold">{{ entry.title }}</h3>
                                <p
                                    v-if="entry.summary"
                                    class="mt-1 text-sm text-muted-foreground"
                                >
                                    {{ entry.summary }}
                                </p>
                            </div>

                            <div
                                class="page-help-content"
                                v-html="entry.content"
                            />
                        </article>
                    </template>

                    <div
                        v-else
                        class="rounded-lg border border-dashed p-5 text-sm text-muted-foreground"
                    >
                        No help content has been published for this page yet.
                    </div>

                    <Button
                        v-if="help.can_manage && help.manage_url"
                        as-child
                        variant="outline"
                        class="w-full"
                    >
                        <Link :href="help.manage_url">
                            Manage help for this page
                            <ExternalLink class="ml-2 h-4 w-4" aria-hidden="true" />
                        </Link>
                    </Button>

                    <p class="text-xs text-muted-foreground">
                        Page key: <code>{{ help.key }}</code>
                    </p>
                </div>
            </div>
        </aside>
    </Teleport>
</template>

<style>
.page-help-content {
    color: var(--foreground);
    font-size: 0.9375rem;
    line-height: 1.65;
}

.page-help-content > :first-child {
    margin-top: 0;
}

.page-help-content > :last-child {
    margin-bottom: 0;
}

.page-help-content h2 {
    margin-top: 1.75rem;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border);
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.35;
    letter-spacing: -0.01em;
}

.page-help-content h3 {
    margin-top: 1.25rem;
    margin-bottom: 0.35rem;
    font-size: 0.9375rem;
    font-weight: 700;
    line-height: 1.4;
}

.page-help-content p {
    margin: 0.5rem 0 1rem;
    color: var(--foreground);
}

.page-help-content ul,
.page-help-content ol {
    margin: 0.75rem 0 1rem;
    padding-left: 1.4rem;
}

.page-help-content ul {
    list-style-type: disc;
}

.page-help-content ol {
    list-style-type: decimal;
}

.page-help-content li {
    margin: 0.4rem 0;
    padding-left: 0.2rem;
}

.page-help-content li::marker {
    color: var(--muted-foreground);
    font-weight: 600;
}

.page-help-content strong {
    font-weight: 700;
    color: var(--foreground);
}

.page-help-content a {
    color: var(--primary);
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 3px;
}

.page-help-content blockquote {
    margin: 1rem 0;
    padding: 0.75rem 1rem;
    border-left: 3px solid var(--border);
    border-radius: 0 0.375rem 0.375rem 0;
    background: var(--muted);
    color: var(--muted-foreground);
}

.page-help-content code {
    border-radius: 0.25rem;
    background: var(--muted);
    padding: 0.1rem 0.3rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
        monospace;
    font-size: 0.85em;
}

.page-help-content table {
    width: 100%;
    margin: 1rem 0;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.page-help-content th,
.page-help-content td {
    padding: 0.625rem;
    border: 1px solid var(--border);
    text-align: left;
    vertical-align: top;
}

.page-help-content th {
    background: var(--muted);
    font-weight: 700;
}
</style>

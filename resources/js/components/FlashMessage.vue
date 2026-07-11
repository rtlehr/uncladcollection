<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CircleCheck, CircleX, Info, TriangleAlert, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';

type FlashTone = 'success' | 'error' | 'warning' | 'info';
type FlashItem = { id: string; tone: FlashTone; message: string };

const page = usePage<{ flash?: Partial<Record<FlashTone, string | null>> }>();
const dismissed = ref<Set<string>>(new Set());

const flashItems = computed<FlashItem[]>(() => {
    const flash = page.props.flash ?? {};
    return (['success', 'error', 'warning', 'info'] as FlashTone[])
        .filter((tone) => Boolean(flash[tone]))
        .map((tone) => ({ id: `${tone}:${flash[tone]}`, tone, message: String(flash[tone]) }))
        .filter((item) => !dismissed.value.has(item.id));
});

watch(() => page.url, () => { dismissed.value = new Set(); });

const toneClasses: Record<FlashTone, string> = {
    success: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-900 dark:text-emerald-100',
    error: 'border-red-500/25 bg-red-500/10 text-red-900 dark:text-red-100',
    warning: 'border-amber-500/25 bg-amber-500/10 text-amber-900 dark:text-amber-100',
    info: 'border-sky-500/25 bg-sky-500/10 text-sky-900 dark:text-sky-100',
};

const iconClasses: Record<FlashTone, string> = {
    success: 'text-emerald-600 dark:text-emerald-300',
    error: 'text-red-600 dark:text-red-300',
    warning: 'text-amber-600 dark:text-amber-300',
    info: 'text-sky-600 dark:text-sky-300',
};

function dismiss(id: string): void {
    dismissed.value = new Set([...dismissed.value, id]);
}
</script>

<template>
    <div
        v-if="flashItems.length"
        class="mx-auto w-full max-w-[1600px] space-y-2 px-4 pt-4 sm:px-6 lg:px-8"
        aria-live="polite"
    >
        <div
            v-for="item in flashItems"
            :key="item.id"
            :class="['flex items-start gap-3 rounded-xl border px-4 py-3 shadow-sm', toneClasses[item.tone]]"
            :role="item.tone === 'error' ? 'alert' : 'status'"
        >
            <CircleCheck v-if="item.tone === 'success'" :class="['mt-0.5 h-5 w-5 shrink-0', iconClasses[item.tone]]" />
            <CircleX v-else-if="item.tone === 'error'" :class="['mt-0.5 h-5 w-5 shrink-0', iconClasses[item.tone]]" />
            <TriangleAlert v-else-if="item.tone === 'warning'" :class="['mt-0.5 h-5 w-5 shrink-0', iconClasses[item.tone]]" />
            <Info v-else :class="['mt-0.5 h-5 w-5 shrink-0', iconClasses[item.tone]]" />

            <p class="min-w-0 flex-1 text-sm font-medium leading-6">{{ item.message }}</p>

            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                class="-mr-1 -mt-1 shrink-0"
                :aria-label="`Dismiss ${item.tone} message`"
                @click="dismiss(item.id)"
            >
                <X class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>

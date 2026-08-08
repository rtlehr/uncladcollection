<script setup lang="ts">
const props = defineProps<{ stages?: Array<{ key: string; label: string; state: string; description?: string; required?: boolean }> }>();

const stateLabel = (state: string) => ({
    complete: 'Complete',
    current: 'Current',
    attention: 'Needs attention',
    pending: 'Pending',
}[state] ?? state);

const stateClass = (state: string) => ({
    complete: 'border-emerald-500/40 bg-emerald-500/10',
    current: 'border-primary/50 bg-primary/5',
    attention: 'border-amber-500/50 bg-amber-500/10',
    pending: 'border-muted bg-muted/20',
}[state] ?? 'border-muted');

const dotClass = (state: string) => ({
    complete: 'bg-emerald-500',
    current: 'bg-primary',
    attention: 'bg-amber-500',
    pending: 'bg-muted-foreground/40',
}[state] ?? 'bg-muted-foreground/40');
</script>

<template>
    <div class="rounded-xl border p-5">
        <div>
            <h2 class="font-semibold">Campaign progress</h2>
            <p class="mt-1 text-sm text-muted-foreground">A quick view of where this campaign is in the operating workflow.</p>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-3 xl:grid-cols-6">
            <div
                v-for="stage in props.stages ?? []"
                :key="stage.key"
                class="rounded-lg border p-3"
                :class="stateClass(stage.state)"
            >
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full" :class="dotClass(stage.state)" />
                    <p class="font-medium">{{ stage.label }}</p>
                </div>
                <p class="mt-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ stateLabel(stage.state) }}</p>
                <p v-if="stage.description" class="mt-1 text-xs text-muted-foreground">{{ stage.description }}</p>
                <p v-if="stage.required === false" class="mt-1 text-[11px] text-muted-foreground">Non-blocking</p>
            </div>
        </div>
    </div>
</template>

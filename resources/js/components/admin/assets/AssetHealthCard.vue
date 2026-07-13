<script setup lang="ts">
import { CheckCircle2, CircleAlert, CircleDashed } from '@lucide/vue';
import type { AssetHealth } from '@/types/adminAsset';

defineProps<{ health: AssetHealth }>();
</script>

<template>
    <section class="rounded-xl border bg-background p-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium">Asset health</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    A quick publishing-readiness check.
                </p>
            </div>
            <div class="text-right">
                <p class="text-3xl font-semibold tabular-nums">{{ health.score }}%</p>
                <p class="text-xs capitalize text-muted-foreground">
                    {{ health.status.replace('_', ' ') }}
                </p>
            </div>
        </div>

        <div class="mt-4 h-2 overflow-hidden rounded-full bg-muted">
            <div
                class="h-full rounded-full bg-primary transition-all"
                :style="{ width: `${health.score}%` }"
            />
        </div>

        <div class="mt-5 grid gap-2 sm:grid-cols-2">
            <div
                v-for="check in health.checks"
                :key="check.key"
                class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm"
            >
                <CheckCircle2 v-if="check.complete" class="h-4 w-4 text-emerald-600" />
                <CircleAlert v-else-if="health.status === 'needs_attention'" class="h-4 w-4 text-amber-600" />
                <CircleDashed v-else class="h-4 w-4 text-muted-foreground" />
                <span :class="check.complete ? '' : 'text-muted-foreground'">{{ check.label }}</span>
            </div>
        </div>
    </section>
</template>

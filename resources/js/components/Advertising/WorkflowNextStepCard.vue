<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle2, Clock3 } from '@lucide/vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    step?: {
        eyebrow?: string;
        title: string;
        description?: string | null;
        status?: string | null;
        action?: {
            label: string;
            href: string;
            method?: 'get' | 'post';
            data?: Record<string, unknown>;
        } | null;
        secondary?: {
            label: string;
            href: string;
        } | null;
    } | null;
}>();

const runAction = () => {
    const action = props.step?.action;
    if (!action || action.method !== 'post') return;
    router.post(action.href, action.data ?? {}, { preserveScroll: true });
};
</script>

<template>
    <section v-if="step" class="rounded-xl border border-primary/30 bg-primary/5 p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex gap-3">
                <div class="mt-0.5 rounded-lg bg-primary/10 p-2 text-primary">
                    <CheckCircle2 v-if="step.status === 'complete'" class="size-5" />
                    <Clock3 v-else class="size-5" />
                </div>
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-primary">{{ step.eyebrow || 'Next workflow step' }}</div>
                    <h2 class="mt-1 text-lg font-semibold">{{ step.title }}</h2>
                    <p v-if="step.description" class="mt-1 max-w-3xl text-sm text-muted-foreground">{{ step.description }}</p>
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap gap-2">
                <Button v-if="step.action?.method === 'post'" @click="runAction">
                    {{ step.action.label }} <ArrowRight class="ml-1 size-4" />
                </Button>
                <Link v-else-if="step.action" :href="step.action.href">
                    <Button>{{ step.action.label }} <ArrowRight class="ml-1 size-4" /></Button>
                </Link>
                <Link v-if="step.secondary" :href="step.secondary.href">
                    <Button variant="outline">{{ step.secondary.label }}</Button>
                </Link>
            </div>
        </div>
    </section>
</template>

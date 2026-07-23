<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';

defineProps<{ title: string; description: string; href: string }>();
</script>

<template>
    <Link :href="href" class="group rounded-xl border bg-card p-5 transition hover:border-primary/40 hover:bg-muted/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="font-semibold">{{ title }}</h3>
                <p class="mt-2 text-sm leading-6 text-muted-foreground">{{ description }}</p>
            </div>
            <ArrowRight class="mt-0.5 h-4 w-4 shrink-0 transition-transform group-hover:translate-x-1" />
        </div>
    </Link>
</template>

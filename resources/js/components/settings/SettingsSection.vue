<script setup lang="ts">
import Heading from '@/components/Heading.vue';

withDefaults(
    defineProps<{
        title: string;
        description?: string | null;
        divided?: boolean;
    }>(),
    {
        description: null,
        divided: false,
    },
);
</script>

<template>
    <section
        :class="[
            'space-y-6',
            divided ? 'border-t border-border pt-6' : '',
        ]"
    >
        <Heading
            variant="small"
            :title="title"
            :description="description ?? undefined"
        />

        <slot />
    </section>
</template>

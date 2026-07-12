<script setup lang="ts">
import {
    onBeforeUnmount,
    onMounted,
    ref,
} from 'vue';

const progress = ref(0);

function updateProgress(): void {
    const article = document.getElementById('article-content');

    if (!article) {
        progress.value = 0;
        return;
    }

    const rect = article.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const total = article.offsetHeight - viewportHeight;
    const travelled = Math.min(
        Math.max(-rect.top, 0),
        Math.max(total, 1),
    );

    progress.value = Math.min(
        100,
        Math.max(0, (travelled / Math.max(total, 1)) * 100),
    );
}

onMounted(() => {
    updateProgress();
    window.addEventListener('scroll', updateProgress, { passive: true });
    window.addEventListener('resize', updateProgress);
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', updateProgress);
    window.removeEventListener('resize', updateProgress);
});
</script>

<template>
    <div
        class="fixed inset-x-0 top-0 z-[110] h-1 bg-transparent"
        aria-hidden="true"
    >
        <div
            class="h-full bg-[var(--brand-accent)] shadow-[0_0_12px_color-mix(in_srgb,var(--brand-accent)_55%,transparent)] transition-[width] duration-100"
            :style="{ width: `${progress}%` }"
        />
    </div>
</template>

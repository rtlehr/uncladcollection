<script setup lang="ts">
import { useNavigationState } from '@/composables/useNavigationState';
const { navigating } = useNavigationState();
</script>

<template>
    <Transition
        enter-active-class="transition-opacity duration-150"
        enter-from-class="opacity-0"
        leave-active-class="transition-opacity duration-200"
        leave-to-class="opacity-0"
    >
        <div
            v-if="navigating"
            class="pointer-events-none fixed inset-x-0 top-0 z-[100] h-0.5 overflow-hidden bg-primary/20"
            aria-hidden="true"
        >
            <div class="h-full w-1/3 animate-[uc-navigation-progress_1s_ease-in-out_infinite] bg-primary" />
        </div>
    </Transition>

    <span class="sr-only" role="status" aria-live="polite">
        {{ navigating ? 'Loading page' : '' }}
    </span>
</template>

<style scoped>
@keyframes uc-navigation-progress {
    0% { transform: translateX(-120%); }
    100% { transform: translateX(420%); }
}
</style>

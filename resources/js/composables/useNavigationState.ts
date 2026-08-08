import { router } from '@inertiajs/vue3';
import { readonly, ref } from 'vue';

const navigating = ref(false);
let initialized = false;

export function initializeNavigationState(): void {
    if (initialized) {
return;
}

    initialized = true;

    router.on('start', () => {
 navigating.value = true; 
});
    router.on('finish', () => {
 navigating.value = false; 
});
    router.on('invalid', () => {
 navigating.value = false; 
});
    router.on('exception', () => {
 navigating.value = false; 
});
}

export function useNavigationState() {
    return { navigating: readonly(navigating) };
}

import {
    onBeforeUnmount,
    ref,
    watch
    
} from 'vue';
import type {Ref} from 'vue';

export function useDebouncedValue<T>(
    source: Ref<T>,
    delay = 300,
): Ref<T> {
    const debounced = ref(source.value) as Ref<T>;
    let timer: ReturnType<typeof setTimeout> | null = null;

    watch(source, (value) => {
        if (timer) {
            clearTimeout(timer);
        }

        timer = setTimeout(() => {
            debounced.value = value;
        }, delay);
    });

    onBeforeUnmount(() => {
        if (timer) {
            clearTimeout(timer);
        }
    });

    return debounced;
}

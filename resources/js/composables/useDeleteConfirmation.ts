import { ref  } from 'vue';
import type {Ref} from 'vue';

export interface DeleteConfirmationState<T> {
    selected: Ref<T | null>;
    open: Ref<boolean>;
    processing: Ref<boolean>;
    requestDelete: (item: T) => void;
    cancelDelete: () => void;
    completeDelete: () => void;
    runDelete: (
        callback: (item: T, finish: () => void) => void,
    ) => void;
}

export function useDeleteConfirmation<T>(): DeleteConfirmationState<T> {
    const selected = ref<T | null>(null) as Ref<T | null>;
    const open = ref(false);
    const processing = ref(false);

    function requestDelete(item: T): void {
        selected.value = item;
        open.value = true;
    }

    function cancelDelete(): void {
        if (processing.value) {
            return;
        }

        selected.value = null;
        open.value = false;
    }

    function completeDelete(): void {
        processing.value = false;
        selected.value = null;
        open.value = false;
    }

    function runDelete(
        callback: (item: T, finish: () => void) => void,
    ): void {
        if (!selected.value || processing.value) {
            return;
        }

        processing.value = true;
        callback(selected.value, completeDelete);
    }

    return {
        selected,
        open,
        processing,
        requestDelete,
        cancelDelete,
        completeDelete,
        runDelete,
    };
}

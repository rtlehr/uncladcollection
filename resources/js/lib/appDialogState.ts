import { reactive } from 'vue';

export type AppDialogOptions = {
    title?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    destructive?: boolean;
};

export type AppPromptOptions = AppDialogOptions & {
    defaultValue?: string;
    placeholder?: string;
    inputType?: 'text' | 'number' | 'email' | 'url';
};

export type DialogMode = 'alert' | 'confirm' | 'prompt';

export type DialogRequest = {
    mode: DialogMode;
    message: string;
    title: string;
    confirmLabel: string;
    cancelLabel: string;
    destructive: boolean;
    defaultValue: string;
    placeholder: string;
    inputType: 'text' | 'number' | 'email' | 'url';
    resolve: (value: boolean | string | null | undefined) => void;
};

export const appDialogState = reactive<{
    open: boolean;
    request: DialogRequest | null;
}>({
    open: false,
    request: null,
});

const queue: DialogRequest[] = [];

function defaultTitle(mode: DialogMode): string {
    if (mode === 'alert') return 'Notice';
    if (mode === 'prompt') return 'Enter information';
    return 'Confirm action';
}

function showNext(): void {
    if (appDialogState.request || queue.length === 0) return;

    appDialogState.request = queue.shift() ?? null;
    appDialogState.open = Boolean(appDialogState.request);
}

export function enqueueAppDialog(
    mode: DialogMode,
    message: string,
    options: AppPromptOptions = {},
): Promise<boolean | string | null | undefined> {
    return new Promise((resolve) => {
        queue.push({
            mode,
            message,
            title: options.title ?? defaultTitle(mode),
            confirmLabel: options.confirmLabel ?? (mode === 'alert' ? 'OK' : 'Continue'),
            cancelLabel: options.cancelLabel ?? 'Cancel',
            destructive: options.destructive ?? false,
            defaultValue: options.defaultValue ?? '',
            placeholder: options.placeholder ?? '',
            inputType: options.inputType ?? 'text',
            resolve,
        });
        showNext();
    });
}

export function resolveAppDialog(value?: boolean | string | null): void {
    const request = appDialogState.request;
    if (!request) return;

    request.resolve(value);
    appDialogState.open = false;
    appDialogState.request = null;
    queueMicrotask(showNext);
}

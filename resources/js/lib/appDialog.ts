import { createApp } from 'vue';

import AppDialogHost from '@/Components/Shared/AppDialogHost.vue';
import {
    enqueueAppDialog,
    type AppDialogOptions,
    type AppPromptOptions,
} from '@/lib/appDialogState';

export type { AppDialogOptions, AppPromptOptions } from '@/lib/appDialogState';

let mounted = false;

export async function appAlert(message: string, options: AppDialogOptions = {}): Promise<void> {
    await enqueueAppDialog('alert', message, options);
}

export async function appConfirm(message: string, options: AppDialogOptions = {}): Promise<boolean> {
    return (await enqueueAppDialog('confirm', message, options)) === true;
}

export async function appPrompt(message: string, options: AppPromptOptions = {}): Promise<string | null> {
    const result = await enqueueAppDialog('prompt', message, options);
    return typeof result === 'string' ? result : null;
}

export function initializeAppDialogs(): void {
    if (mounted || typeof document === 'undefined') return;

    const mountPoint = document.createElement('div');
    mountPoint.id = 'app-dialog-host';
    document.body.appendChild(mountPoint);
    createApp(AppDialogHost).mount(mountPoint);
    mounted = true;
}

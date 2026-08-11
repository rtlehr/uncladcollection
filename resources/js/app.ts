import { createInertiaApp } from '@inertiajs/vue3';

import { initializeTheme } from '@/composables/useAppearance';
import { initializeNavigationState } from '@/composables/useNavigationState';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import PublicBlankLayout from '@/layouts/PublicBlankLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeAppDialogs } from '@/lib/appDialog';
import { initializeMessageBoxes } from '@/lib/messageBoxes';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => {
        if (!title) {
            return appName;
        }

        return title
            .toLowerCase()
            .includes(appName.toLowerCase())
            ? title
            : `${title} - ${appName}`;
    },

    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;

            case name.startsWith('auth/'):
                return AuthLayout;

            case name.startsWith('settings/'):
                return [PublicBlankLayout, SettingsLayout];

            case name.startsWith('Account/'):
            case name.startsWith('Purchases/'):
                return PublicBlankLayout;

            default:
                return AppLayout;
        }
    },

    progress: {
        color: '#4B5563',
        delay: 250,
        includeCSS: true,
        showSpinner: false,
    },
});

initializeTheme();
initializeNavigationState();
initializeFlashToast();
initializeAppDialogs();
initializeMessageBoxes();

if (typeof document !== 'undefined') {
    document.documentElement.classList.add('js');

    requestAnimationFrame(() => {
        document.body.dataset.pageReady = 'true';
    });
}

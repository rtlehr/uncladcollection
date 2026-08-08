<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ShieldAlert, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';

type ImpersonationPayload = {
    active: boolean;
    original_user?: { id: number; name: string; email: string };
    target_user?: { id: number; name: string; email: string };
    started_at?: string | null;
    stop_url?: string;
};

const page = usePage<{ impersonation?: ImpersonationPayload }>();
const payload = computed(() => page.props.impersonation ?? { active: false });
const stopping = ref(false);

function stopImpersonating(): void {
    if (!payload.value.stop_url || stopping.value) {
return;
}

    stopping.value = true;
    router.post(payload.value.stop_url, {}, {
        preserveScroll: false,
        onFinish: () => {
 stopping.value = false; 
},
    });
}
</script>

<template>
    <section
        v-if="payload.active && payload.target_user"
        class="sticky top-0 z-[120] border-b border-amber-300 bg-amber-100 text-amber-950 shadow-sm dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100"
        role="status"
        aria-live="polite"
    >
        <div class="mx-auto flex max-w-[1600px] flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <div class="flex min-w-0 items-start gap-3">
                <ShieldAlert class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                <div class="min-w-0">
                    <p class="font-semibold">Impersonating {{ payload.target_user.name }}</p>
                    <p class="truncate text-sm opacity-85">
                        {{ payload.target_user.email }} · Sensitive account, payment, download, and support actions are disabled.
                    </p>
                </div>
            </div>

            <Button
                type="button"
                variant="outline"
                class="border-amber-500 bg-white/80 hover:bg-white dark:bg-amber-900 dark:hover:bg-amber-800"
                :disabled="stopping"
                @click="stopImpersonating"
            >
                <X class="mr-2 h-4 w-4" aria-hidden="true" />
                {{ stopping ? 'Restoring administrator…' : 'Stop impersonating' }}
            </Button>
        </div>
    </section>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

import ConfirmActionDialog from '@/Components/Shared/ConfirmActionDialog.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import SectionHeader from '@/Components/Shared/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import { useConfirmAction } from '@/composables/useConfirmAction';

const standardDialog = useConfirmAction();
const destructiveDialog = useConfirmAction();
const loadingDialog = useConfirmAction();

const lastAction = ref('No action confirmed yet.');
const isLoading = ref(false);

function confirmStandard() {
    lastAction.value = 'Standard action confirmed.';
    standardDialog.close();
}

function confirmDestructive() {
    lastAction.value = 'Destructive action confirmed.';
    destructiveDialog.close();
}

function confirmLoading() {
    isLoading.value = true;
    lastAction.value = 'Loading state example activated.';

    window.setTimeout(() => {
        isLoading.value = false;
        loadingDialog.close();
        lastAction.value = 'Loading action completed.';
    }, 1000);
}
</script>

<template>
    <Head title="Confirm Dialog Showcase" />

    <div class="space-y-10 p-6">
        <PageHeader
            eyebrow="Development"
            title="Confirm Dialog Showcase"
            description="Examples of the reusable confirmation-dialog system."
        />

        <section>
            <SectionHeader
                title="Dialog Variants"
                description="Use the standard variant for important actions and the destructive variant for irreversible actions."
            />

            <div class="flex flex-wrap gap-3">
                <Button @click="standardDialog.open">
                    Standard Confirmation
                </Button>

                <Button
                    variant="destructive"
                    @click="destructiveDialog.open"
                >
                    Destructive Confirmation
                </Button>

                <Button
                    variant="outline"
                    @click="loadingDialog.open"
                >
                    Loading State
                </Button>
            </div>
        </section>

        <section class="rounded-lg border bg-card p-6 shadow-sm">
            <h2 class="text-lg font-semibold">
                Last Result
            </h2>

            <p class="mt-2 text-sm text-muted-foreground">
                {{ lastAction }}
            </p>
        </section>

        <ConfirmActionDialog
            v-model:open="standardDialog.isOpen.value"
            title="Continue with this action?"
            description="This example represents an important but reversible action."
            confirm-label="Continue"
            @confirm="confirmStandard"
        />

        <ConfirmActionDialog
            v-model:open="destructiveDialog.isOpen.value"
            title="Delete this record?"
            description="This action cannot be undone."
            confirm-label="Delete"
            destructive
            @confirm="confirmDestructive"
        >
            <p class="text-sm text-muted-foreground">
                Any related data may also be removed.
            </p>
        </ConfirmActionDialog>

        <ConfirmActionDialog
            v-model:open="loadingDialog.isOpen.value"
            title="Run the loading example?"
            description="The dialog will remain open while the simulated action runs."
            confirm-label="Run Action"
            :loading="isLoading"
            @confirm="confirmLoading"
        />
    </div>
</template>

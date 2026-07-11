<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

import FormActions from '@/Components/Forms/FormActions.vue';
import FormField from '@/Components/Forms/FormField.vue';
import FormGrid from '@/Components/Forms/FormGrid.vue';
import FormSection from '@/Components/Forms/FormSection.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import SectionHeader from '@/Components/Shared/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const name = ref('Sample User');
const username = ref('sample-user');
const email = ref('sample@example.com');
const title = ref('');
const processing = ref(false);
const result = ref('No action submitted yet.');

function submitExample() {
    processing.value = true;
    result.value = 'Saving sample form...';

    window.setTimeout(() => {
        processing.value = false;
        result.value = 'Sample form saved.';
    }, 900);
}

function cancelExample() {
    result.value = 'Cancel clicked.';
}
</script>

<template>
    <Head title="Form Framework Showcase" />

    <div class="space-y-10 p-6">
        <PageHeader
            eyebrow="Development"
            title="Form Framework Showcase"
            description="Examples of reusable form sections, fields, grids, and actions."
        />

        <section>
            <SectionHeader
                title="Standard Form"
                description="A typical two-column admin form."
            />

            <div class="space-y-6">
                <FormSection
                    title="Account Information"
                    description="Basic account and profile information."
                >
                    <template #headerActions>
                        <Button variant="outline" size="sm">
                            Header Action
                        </Button>
                    </template>

                    <FormGrid :columns="2">
                        <FormField
                            label="Name"
                            for-id="name"
                            required
                            description="The member's display name."
                        >
                            <Input id="name" v-model="name" />
                        </FormField>

                        <FormField
                            label="Username"
                            for-id="username"
                        >
                            <Input id="username" v-model="username" />
                        </FormField>

                        <FormField
                            label="Email"
                            for-id="email"
                            required
                        >
                            <Input id="email" v-model="email" type="email" />
                        </FormField>

                        <FormField
                            label="Title"
                            for-id="title"
                            error="This sample error demonstrates validation styling."
                        >
                            <Input id="title" v-model="title" />
                        </FormField>
                    </FormGrid>
                </FormSection>

                <FormSection
                    title="Horizontal Fields"
                    description="Useful for settings and detail-heavy forms."
                >
                    <div class="space-y-6">
                        <FormField
                            label="Public Name"
                            for-id="public-name"
                            description="This name appears publicly."
                            layout="horizontal"
                        >
                            <Input id="public-name" v-model="name" />
                        </FormField>

                        <FormField
                            label="Public Email"
                            for-id="public-email"
                            description="Used for account-related notifications."
                            layout="horizontal"
                        >
                            <Input id="public-email" v-model="email" />
                        </FormField>
                    </div>
                </FormSection>

                <FormActions
                    :processing="processing"
                    submit-label="Save Example"
                    @submit="submitExample"
                    @cancel="cancelExample"
                >
                    <template #destructive>
                        <Button variant="destructive">
                            Delete Example
                        </Button>
                    </template>
                </FormActions>
            </div>
        </section>

        <section class="rounded-lg border bg-card p-6 shadow-sm">
            <h2 class="text-lg font-semibold">
                Last Result
            </h2>

            <p class="mt-2 text-sm text-muted-foreground">
                {{ result }}
            </p>
        </section>
    </div>
</template>

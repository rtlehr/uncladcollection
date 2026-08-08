<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { RotateCcw, Send } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type Revision = {
    id: number;
    revision_number: number;
    created_at: string;
    created_by?: { id: number; name: string } | null;
};

type EmailTemplate = {
    id: number;
    key: string;
    name: string;
    category: string;
    description: string | null;
    subject: string;
    preview_text: string | null;
    body_html: string;
    body_text: string | null;
    variables: string[];
    required_variables: string[];
    is_transactional: boolean;
    is_active: boolean;
    revisions: Revision[];
};

const props = defineProps<{ template: EmailTemplate }>();
const testEmail = ref('');
const testError = ref('');

const form = useForm({
    subject: props.template.subject,
    preview_text: props.template.preview_text ?? '',
    body_html: props.template.body_html,
    body_text: props.template.body_text ?? '',
    is_active: props.template.is_active,
});

const previewHtml = computed(() => {
    let content = form.body_html;

    for (const variable of props.template.variables) {
        content = content.replaceAll(`{{ ${variable} }}`, `<strong>[${variable}]</strong>`);
        content = content.replaceAll(`{{${variable}}}`, `<strong>[${variable}]</strong>`);
    }

    return content;
});

function submit(): void {
    form.put(`/admin/communications/email-templates/${props.template.id}`, { preserveScroll: true });
}

function restoreDefault(): void {
    if (!confirm('Restore the code-provided system default? The current version will remain in revision history.')) {
return;
}

    router.post(`/admin/communications/email-templates/${props.template.id}/restore`, {}, { preserveScroll: true });
}

function sendTest(): void {
    testError.value = '';

    if (!testEmail.value.trim()) {
        testError.value = 'Enter an email address.';

        return;
    }

    router.post(
        `/admin/communications/email-templates/${props.template.id}/test`,
        { email: testEmail.value },
        { preserveScroll: true, onError: (errors) => (testError.value = String(errors.email ?? 'Unable to send test email.')) },
    );
}
</script>

<template>
    <Head :title="`Edit ${template.name}`" />

    <div class="space-y-6 p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <PageHeader :title="template.name" :description="template.description ?? 'Manage this email template.'" />
            <Link href="/admin/communications/email-templates"><Button variant="outline">Back to templates</Button></Link>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
            <form class="space-y-6" @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <CardTitle>Template content</CardTitle>
                                <p class="mt-1 font-mono text-xs text-muted-foreground">{{ template.key }}</p>
                            </div>
                            <div class="flex gap-2">
                                <Badge variant="outline">{{ template.category }}</Badge>
                                <Badge v-if="template.is_transactional">Transactional</Badge>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div class="space-y-2">
                            <Label for="subject">Subject</Label>
                            <Input id="subject" v-model="form.subject" />
                            <InputError :message="form.errors.subject" />
                        </div>

                        <div class="space-y-2">
                            <Label for="preview_text">Preview text</Label>
                            <Input id="preview_text" v-model="form.preview_text" />
                            <InputError :message="form.errors.preview_text" />
                        </div>

                        <div class="space-y-2">
                            <Label for="body_html">HTML body</Label>
                            <Textarea id="body_html" v-model="form.body_html" class="min-h-72 font-mono text-xs" />
                            <InputError :message="form.errors.body_html" />
                        </div>

                        <div class="space-y-2">
                            <Label for="body_text">Plain-text fallback</Label>
                            <Textarea id="body_text" v-model="form.body_text" class="min-h-52 font-mono text-xs" />
                            <InputError :message="form.errors.body_text" />
                        </div>

                        <label class="flex items-center gap-3 rounded-lg border p-4">
                            <input v-model="form.is_active" type="checkbox" class="h-4 w-4" />
                            <span>
                                <span class="block text-sm font-medium">Template is active</span>
                                <span class="block text-xs text-muted-foreground">Disabled templates fall back to the code-provided default.</span>
                            </span>
                        </label>

                        <div class="flex flex-wrap justify-between gap-3 border-t pt-5">
                            <Button type="button" variant="outline" class="gap-2" @click="restoreDefault">
                                <RotateCcw class="h-4 w-4" /> Restore default
                            </Button>
                            <Button type="submit" :disabled="form.processing">Save template</Button>
                        </div>
                    </CardContent>
                </Card>
            </form>

            <div class="space-y-6">
                <Card>
                    <CardHeader><CardTitle>Available variables</CardTitle></CardHeader>
                    <CardContent class="flex flex-wrap gap-2">
                        <code
                            v-for="variable in template.variables"
                            :key="variable"
                            class="rounded bg-muted px-2 py-1 text-xs"
                            v-text="`{{ ${variable} }}`"
                        />
                        <p class="w-full pt-2 text-xs text-muted-foreground">
                            Required variables cannot be removed: {{ template.required_variables.join(', ') || 'None' }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Preview</CardTitle></CardHeader>
                    <CardContent>
                        <div class="rounded-lg border bg-white p-5 text-sm text-black" v-html="previewHtml" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Send test email</CardTitle></CardHeader>
                    <CardContent class="space-y-3">
                        <Input v-model="testEmail" type="email" placeholder="you@example.com" />
                        <p v-if="testError" class="text-sm text-destructive">{{ testError }}</p>
                        <Button type="button" variant="outline" class="w-full gap-2" @click="sendTest">
                            <Send class="h-4 w-4" /> Send test
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Revision history</CardTitle></CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="revision in template.revisions" :key="revision.id" class="rounded-lg border p-3 text-sm">
                            <p class="font-medium">Revision {{ revision.revision_number }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ new Date(revision.created_at).toLocaleString() }}
                                <span v-if="revision.created_by"> · {{ revision.created_by.name }}</span>
                            </p>
                        </div>
                        <p v-if="!template.revisions.length" class="text-sm text-muted-foreground">No previous revisions yet.</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>

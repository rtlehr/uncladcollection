<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Provider {
    id: number; name: string; slug: string; driver: string; base_url: string; api_key_masked: string|null;
    default_model: string|null; connect_timeout_seconds: number; timeout_seconds: number; retry_times: number;
    streaming_enabled: boolean; is_enabled: boolean; last_tested_at: string|null; last_test_status: string|null; last_test_message: string|null;
}
interface Feature { key: string; label: string; assignment: Record<string, any> }
const props = defineProps<{ providers: Provider[]; features: Feature[] }>();
const testing = ref<number|null>(null);
const loadingModels = ref<number|null>(null);
const models = ref<Record<number, Array<{id:string;name:string}>>>({});

const providerForms = props.providers.map((p) => useForm({
    name: p.name, slug: p.slug, driver: p.driver, base_url: p.base_url, api_key: '', default_model: p.default_model ?? '',
    connect_timeout_seconds: p.connect_timeout_seconds, timeout_seconds: p.timeout_seconds, retry_times: p.retry_times,
    streaming_enabled: p.streaming_enabled, is_enabled: p.is_enabled,
}));
const createForm = useForm({ name: '', slug: '', driver: 'venice', base_url: 'https://api.venice.ai/api/v1', api_key: '', default_model: '', connect_timeout_seconds: 20, timeout_seconds: 300, retry_times: 2, streaming_enabled: false, is_enabled: true });
const assignmentForm = useForm({ assignments: props.features.map((f) => ({
    feature: f.key, primary_provider_id: Number(f.assignment.primary_provider_id ?? props.providers[0]?.id ?? 0), primary_model: f.assignment.primary_model ?? '',
    fallback_provider_id: f.assignment.fallback_provider_id ? Number(f.assignment.fallback_provider_id) : null,
    fallback_model: f.assignment.fallback_model ?? '', fallback_enabled: Boolean(f.assignment.fallback_enabled),
}))});

function saveProvider(provider: Provider, index: number) {
 providerForms[index].put(`/admin/ai-providers/${provider.id}`, { preserveScroll: true }); 
}
function createProvider() {
 createForm.post('/admin/ai-providers', { preserveScroll: true, onSuccess: () => createForm.reset() }); 
}
async function testProvider(provider: Provider) {
    testing.value = provider.id;

    try {
        const response = await fetch(`/admin/ai-providers/${provider.id}/test`, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' } });
        const result = await response.json();
        alert(result.success ? `Connected using ${result.model}. ${result.duration_ms} ms.` : result.message);
        router.reload({ only: ['providers'] });
    } finally {
 testing.value = null; 
}
}
async function loadModels(provider: Provider) {
    loadingModels.value = provider.id;

    try {
        const response = await fetch(`/admin/ai-providers/${provider.id}/models`, { headers: { Accept: 'application/json' } });
        const result = await response.json();

        if (!response.ok) {
throw new Error(result.message ?? 'Could not load models.');
}

        models.value[provider.id] = result.models ?? [];
    } catch (e) {
 alert(e instanceof Error ? e.message : 'Could not load models.'); 
} finally {
 loadingModels.value = null; 
}
}
function providerById(id: number|null) {
 return props.providers.find((p) => p.id === Number(id)); 
}
</script>

<template>
<Head title="AI Providers" />
<div class="space-y-6 p-6">
    <PageHeader title="AI Providers" description="Configure Ollama, Venice, OpenAI, and feature-by-feature provider assignments without editing code." />

    <Card>
        <CardHeader><CardTitle>Feature assignments</CardTitle><CardDescription>Choose a primary provider and optional fallback for each AI feature.</CardDescription></CardHeader>
        <CardContent>
            <form class="space-y-5" @submit.prevent="assignmentForm.put('/admin/ai-providers/assignments')">
                <div v-for="(feature, index) in props.features" :key="feature.key" class="rounded-lg border p-4">
                    <h3 class="font-semibold">{{ feature.label }}</h3>
                    <div class="mt-3 grid gap-4 lg:grid-cols-5">
                        <label class="text-sm font-medium">Primary provider<select v-model.number="assignmentForm.assignments[index].primary_provider_id" class="mt-1 w-full rounded-md border bg-background p-2"><option v-for="p in providers.filter(x => x.is_enabled)" :key="p.id" :value="p.id">{{ p.name }}</option></select></label>
                        <label class="text-sm font-medium">Primary model<Input v-model="assignmentForm.assignments[index].primary_model" class="mt-1" :placeholder="providerById(assignmentForm.assignments[index].primary_provider_id)?.default_model ?? 'Provider default'" /></label>
                        <label class="flex items-center gap-2 pt-7 text-sm font-medium"><input v-model="assignmentForm.assignments[index].fallback_enabled" type="checkbox" /> Enable fallback</label>
                        <label class="text-sm font-medium">Fallback provider<select v-model.number="assignmentForm.assignments[index].fallback_provider_id" :disabled="!assignmentForm.assignments[index].fallback_enabled" class="mt-1 w-full rounded-md border bg-background p-2"><option :value="null">None</option><option v-for="p in providers.filter(x => x.is_enabled && x.id !== assignmentForm.assignments[index].primary_provider_id)" :key="p.id" :value="p.id">{{ p.name }}</option></select></label>
                        <label class="text-sm font-medium">Fallback model<Input v-model="assignmentForm.assignments[index].fallback_model" :disabled="!assignmentForm.assignments[index].fallback_enabled" class="mt-1" placeholder="Provider default" /></label>
                    </div>
                </div>
                <Button type="submit" :disabled="assignmentForm.processing">Save feature assignments</Button>
            </form>
        </CardContent>
    </Card>

    <div class="space-y-5">
        <Card v-for="(provider, index) in providers" :key="provider.id">
            <CardHeader><div class="flex flex-wrap items-start justify-between gap-3"><div><CardTitle>{{ provider.name }}</CardTitle><CardDescription>{{ provider.driver }} · {{ provider.last_test_status ?? 'Not tested' }}<span v-if="provider.last_test_message"> · {{ provider.last_test_message }}</span></CardDescription></div><div class="flex gap-2"><Button variant="outline" type="button" :disabled="testing === provider.id" @click="testProvider(provider)">{{ testing === provider.id ? 'Testing…' : 'Test connection' }}</Button><Button variant="outline" type="button" :disabled="loadingModels === provider.id" @click="loadModels(provider)">{{ loadingModels === provider.id ? 'Loading…' : 'Load models' }}</Button></div></div></CardHeader>
            <CardContent><form class="grid gap-4 lg:grid-cols-3" @submit.prevent="saveProvider(provider,index)">
                <div><Label>Name</Label><Input v-model="providerForms[index].name" /></div><div><Label>Slug</Label><Input v-model="providerForms[index].slug" /></div><div><Label>Driver</Label><select v-model="providerForms[index].driver" class="mt-1 w-full rounded-md border bg-background p-2"><option value="ollama">Ollama</option><option value="venice">Venice</option><option value="openai">OpenAI</option></select></div>
                <div class="lg:col-span-2"><Label>Base URL</Label><Input v-model="providerForms[index].base_url" /></div><div><Label>API key</Label><Input v-model="providerForms[index].api_key" type="password" :placeholder="provider.api_key_masked ?? 'Enter API key'" /></div>
                <div><Label>Default model</Label><Input v-model="providerForms[index].default_model" :list="`models-${provider.id}`" /><datalist :id="`models-${provider.id}`"><option v-for="m in models[provider.id] ?? []" :key="m.id" :value="m.id" /></datalist></div>
                <div><Label>Connect timeout</Label><Input v-model.number="providerForms[index].connect_timeout_seconds" type="number" /></div><div><Label>Response timeout</Label><Input v-model.number="providerForms[index].timeout_seconds" type="number" /></div>
                <div><Label>Retries</Label><Input v-model.number="providerForms[index].retry_times" type="number" /></div><label class="flex items-center gap-2 pt-7"><input v-model="providerForms[index].is_enabled" type="checkbox" /> Enabled</label><label class="flex items-center gap-2 pt-7"><input v-model="providerForms[index].streaming_enabled" type="checkbox" /> Streaming</label>
                <div class="lg:col-span-3"><Button type="submit" :disabled="providerForms[index].processing">Save provider</Button></div>
            </form></CardContent>
        </Card>
    </div>

    <Card><CardHeader><CardTitle>Add provider</CardTitle><CardDescription>Add another OpenAI-compatible or Ollama provider.</CardDescription></CardHeader><CardContent><form class="grid gap-4 lg:grid-cols-3" @submit.prevent="createProvider"><div><Label>Name</Label><Input v-model="createForm.name" /></div><div><Label>Slug</Label><Input v-model="createForm.slug" /></div><div><Label>Driver</Label><select v-model="createForm.driver" class="mt-1 w-full rounded-md border bg-background p-2"><option value="venice">Venice</option><option value="openai">OpenAI</option><option value="ollama">Ollama</option></select></div><div class="lg:col-span-2"><Label>Base URL</Label><Input v-model="createForm.base_url" /></div><div><Label>API key</Label><Input v-model="createForm.api_key" type="password" /></div><div><Label>Default model</Label><Input v-model="createForm.default_model" /></div><div><Label>Connect timeout</Label><Input v-model.number="createForm.connect_timeout_seconds" type="number" /></div><div><Label>Response timeout</Label><Input v-model.number="createForm.timeout_seconds" type="number" /></div><div><Label>Retries</Label><Input v-model.number="createForm.retry_times" type="number" /></div><label class="flex items-center gap-2 pt-7"><input v-model="createForm.is_enabled" type="checkbox" /> Enabled</label><div class="lg:col-span-3"><Button type="submit" :disabled="createForm.processing">Add provider</Button></div></form></CardContent></Card>
</div>
</template>

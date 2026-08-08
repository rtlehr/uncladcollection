<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import SavedPromptForm from '@/components/admin/ai/SavedPromptForm.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import { Button } from '@/components/ui/button';

const generating = ref(false);
const generationError = ref('');
const form = useForm({
    title: '', description: '', prompt_text: '', intended_use: 'general_image', content_context: 'adult_naturism', output_mode: 'content_only',
    body_detail_level: 'detailed_adult_anatomy', description_depth: 'expanded', character_detail_level: 'very_detailed', environment_detail_level: 'rich',
    describe_every_visible_person: true, orientation: 'landscape', additional_instructions: '', provider: '', model: '', source_generation_id: null as number | null,
});

async function generate() {
    generating.value = true; generationError.value = '';

    try {
        const response = await fetch('/admin/ai-content/image-prompts/generate', { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' }, body: JSON.stringify(form.data()) });
        const payload = await response.json();

        if (!response.ok) {
throw new Error(payload.message ?? 'Prompt generation failed.');
}

        form.prompt_text = payload.prompt; form.provider = payload.provider ?? ''; form.model = payload.model ?? ''; form.source_generation_id = payload.generation_id ?? null;

        if (!form.title.trim()) {
form.title = form.description.slice(0, 120);
}
    } catch (e) {
 generationError.value = e instanceof Error ? e.message : 'Prompt generation failed.'; 
} finally {
 generating.value = false; 
}
}

function save() {
 form.post('/admin/ai-content/image-prompts'); 
}
</script>

<template><Head title="Create Saved AI Prompt"/><AppLayout><div class="space-y-6 p-6"><PageHeader title="Create New AI Prompt" description="Generate a new image prompt, edit it if needed, and save it to your prompt library."/><ShowSection title="Prompt generator"><SavedPromptForm :form="form" :generating="generating" show-generate @generate="generate"/><p v-if="generationError" class="mt-3 text-sm text-destructive">{{ generationError }}</p></ShowSection><div class="flex justify-end gap-3"><Button variant="outline" @click="router.visit('/admin/ai-content/image-prompts')">Cancel</Button><Button :disabled="form.processing || !form.prompt_text.trim()" @click="save">{{ form.processing ? 'Saving...' : 'Save Prompt' }}</Button></div></div></AppLayout></template>

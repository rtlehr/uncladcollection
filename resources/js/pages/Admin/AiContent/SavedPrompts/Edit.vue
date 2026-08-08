<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Copy, RotateCcw, WandSparkles } from '@lucide/vue';
import { ref } from 'vue';
import SavedPromptForm from '@/components/admin/ai/SavedPromptForm.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{ savedPrompt: any }>();
const refining = ref(false);
const refinementError = ref('');
const refinementInstruction = ref('');
const copied = ref(false);

const form = useForm({
    title: props.savedPrompt.title,
    description: props.savedPrompt.description ?? '',
    prompt_text: props.savedPrompt.prompt_text,
    intended_use: props.savedPrompt.intended_use,
    content_context: props.savedPrompt.content_context,
    output_mode: props.savedPrompt.output_mode,
    body_detail_level: props.savedPrompt.body_detail_level,
    description_depth: props.savedPrompt.description_depth,
    character_detail_level: props.savedPrompt.character_detail_level,
    environment_detail_level: props.savedPrompt.environment_detail_level,
    describe_every_visible_person: props.savedPrompt.describe_every_visible_person,
    orientation: props.savedPrompt.orientation,
    additional_instructions: props.savedPrompt.additional_instructions ?? '',
    provider: props.savedPrompt.provider ?? '',
    model: props.savedPrompt.model ?? '',
    source_generation_id: props.savedPrompt.source_generation_id ?? null,
    refinement_instruction: '',
});

async function refine() {
    if (!refinementInstruction.value.trim()) {
return;
}

    refining.value = true; refinementError.value = '';

    try {
        const response = await fetch(`/admin/ai-content/image-prompts/${props.savedPrompt.id}/refine`, {
            method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' },
            body: JSON.stringify({ prompt_text: form.prompt_text, instruction: refinementInstruction.value, content_context: form.content_context, output_mode: form.output_mode, body_detail_level: form.body_detail_level, description_depth: form.description_depth, character_detail_level: form.character_detail_level, environment_detail_level: form.environment_detail_level }),
        });
        const payload = await response.json();

        if (!response.ok) {
throw new Error(payload.message ?? 'Prompt refinement failed.');
}

        form.prompt_text = payload.prompt; form.provider = payload.provider ?? form.provider; form.model = payload.model ?? form.model; form.refinement_instruction = refinementInstruction.value;
    } catch (e) {
 refinementError.value = e instanceof Error ? e.message : 'Prompt refinement failed.'; 
} finally {
 refining.value = false; 
}
}

function save() {
 form.put(`/admin/ai-content/image-prompts/${props.savedPrompt.id}`, { preserveScroll: true, onSuccess: () => {
 refinementInstruction.value = ''; form.refinement_instruction = ''; 
} }); 
}
function restore(version: any) {
 if (confirm(`Restore version ${version.version_number}?`)) {
router.post(`/admin/ai-content/image-prompts/${props.savedPrompt.id}/versions/${version.id}/restore`, {}, { preserveScroll: true });
} 
}
async function copyPrompt() {
 await navigator.clipboard.writeText(form.prompt_text); copied.value = true; setTimeout(() => copied.value = false, 1200); 
}
</script>

<template>
    <Head :title="`Edit ${savedPrompt.title}`" />
    <AppLayout>
        <div class="space-y-6 p-6">
            <PageHeader :title="`Edit ${savedPrompt.title}`" description="Fine-tune the prompt manually or ask AI to revise it while preserving version history." />

            <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
                <div class="space-y-6">
                    <ShowSection title="Saved prompt"><SavedPromptForm :form="form" /></ShowSection>

                    <ShowSection title="AI refinement" description="Describe the change you want. The AI will rewrite the complete prompt and place the result in the prompt field above.">
                        <div class="space-y-3">
                            <Textarea v-model="refinementInstruction" rows="4" placeholder="Example: Make some of the people clothed, while keeping the rest naturally nude." />
                            <div class="flex flex-wrap items-center gap-3">
                                <Button type="button" :disabled="refining || !refinementInstruction.trim()" @click="refine"><WandSparkles class="mr-2 h-4 w-4" />{{ refining ? 'Refining...' : 'Refine Prompt' }}</Button>
                                <span class="text-sm text-muted-foreground">Review the rewritten prompt, then click Save Changes to create a new version.</span>
                            </div>
                            <p v-if="refinementError" class="text-sm text-destructive">{{ refinementError }}</p>
                        </div>
                    </ShowSection>
                </div>

                <div class="space-y-6">
                    <ShowSection title="Prompt information">
                        <dl class="space-y-3 text-sm"><div><dt class="font-medium">Provider</dt><dd class="text-muted-foreground">{{ form.provider || 'Not recorded' }}</dd></div><div><dt class="font-medium">Model</dt><dd class="text-muted-foreground">{{ form.model || 'Not recorded' }}</dd></div><div><dt class="font-medium">Created</dt><dd class="text-muted-foreground">{{ new Date(savedPrompt.created_at).toLocaleString() }}</dd></div><div><dt class="font-medium">Last updated</dt><dd class="text-muted-foreground">{{ new Date(savedPrompt.updated_at).toLocaleString() }}</dd></div></dl>
                        <Button class="mt-4" variant="outline" @click="copyPrompt"><Copy class="mr-2 h-4 w-4" />{{ copied ? 'Copied' : 'Copy Current Prompt' }}</Button>
                    </ShowSection>

                    <ShowSection title="Version history" :description="`${savedPrompt.versions?.length ?? 0} saved version(s)`">
                        <div class="max-h-[700px] space-y-3 overflow-y-auto pr-1">
                            <article v-for="version in savedPrompt.versions" :key="version.id" class="rounded-lg border p-3">
                                <div class="flex items-start justify-between gap-2"><div><p class="font-medium">Version {{ version.version_number }}</p><p class="text-xs text-muted-foreground">{{ new Date(version.created_at).toLocaleString() }}<span v-if="version.creator"> · {{ version.creator.name }}</span></p></div><Button size="sm" variant="outline" @click="restore(version)"><RotateCcw class="mr-1 h-3.5 w-3.5" />Restore</Button></div>
                                <p v-if="version.refinement_instruction" class="mt-2 text-xs"><span class="font-medium">Change:</span> {{ version.refinement_instruction }}</p>
                                <p class="mt-2 line-clamp-4 whitespace-pre-wrap text-xs leading-5 text-muted-foreground">{{ version.prompt_text }}</p>
                            </article>
                        </div>
                    </ShowSection>
                </div>
            </div>

            <div class="flex justify-end gap-3"><Button variant="outline" @click="router.visit('/admin/ai-content/image-prompts')">Back to Prompts</Button><Button :disabled="form.processing" @click="save">{{ form.processing ? 'Saving...' : 'Save Changes' }}</Button></div>
        </div>
    </AppLayout>
</template>

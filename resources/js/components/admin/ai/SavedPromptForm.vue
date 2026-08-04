<script setup lang="ts">
import { computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{ form: any; generating?: boolean; showGenerate?: boolean }>();
const emit = defineEmits<{ (e: 'generate'): void }>();

watch(() => props.form.content_context, (context) => {
    if (context === 'adult_naturism') {
        props.form.body_detail_level = 'detailed_adult_anatomy';
        props.form.character_detail_level = 'very_detailed';
    } else if (context === 'family_naturism') {
        props.form.body_detail_level = 'natural_detail';
        props.form.character_detail_level = 'detailed';
    } else {
        props.form.body_detail_level = 'contextual';
        props.form.character_detail_level = 'detailed';
    }
});

const canGenerate = computed(() => String(props.form.description ?? '').trim() !== '');
</script>

<template>
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <label class="text-sm font-medium">Prompt title
                <Input v-model="form.title" class="mt-1" placeholder="Example: Adults watching a football game" />
                <span v-if="form.errors?.title" class="mt-1 block text-sm text-destructive">{{ form.errors.title }}</span>
            </label>
            <label class="text-sm font-medium">Short scene description
                <Input v-model="form.description" class="mt-1" placeholder="Describe the image you want" />
                <span v-if="form.errors?.description" class="mt-1 block text-sm text-destructive">{{ form.errors.description }}</span>
            </label>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <label class="text-sm font-medium">Intended use
                <select v-model="form.intended_use" class="mt-1 w-full rounded-md border bg-background p-2">
                    <option value="general_image">General image</option><option value="blog_header">Blog header</option><option value="blog_inline">Blog inline</option><option value="collection_cover">Collection cover</option><option value="category_banner">Category banner</option><option value="advertisement">Advertisement</option><option value="social_media">Social media</option><option value="email_graphic">Email graphic</option><option value="landing_page">Landing page</option>
                </select>
            </label>
            <label class="text-sm font-medium">Context
                <select v-model="form.content_context" class="mt-1 w-full rounded-md border bg-background p-2">
                    <option value="adult_naturism">Adult naturism</option><option value="family_naturism">Family naturism</option><option value="general">General</option>
                </select>
            </label>
            <label class="text-sm font-medium">Output mode
                <select v-model="form.output_mode" class="mt-1 w-full rounded-md border bg-background p-2">
                    <option value="content_only">Content only</option><option value="content_composition">Content + composition</option><option value="full">Full prompt</option>
                </select>
            </label>
            <label class="text-sm font-medium">Orientation
                <select v-model="form.orientation" class="mt-1 w-full rounded-md border bg-background p-2">
                    <option value="landscape">Landscape</option><option value="portrait">Portrait</option><option value="square">Square</option><option value="auto">Let AI decide</option>
                </select>
            </label>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <label class="text-sm font-medium">Body detail
                <select v-model="form.body_detail_level" class="mt-1 w-full rounded-md border bg-background p-2">
                    <option value="contextual">Contextual</option><option value="natural_detail">Natural detail</option><option value="detailed_adult_anatomy">Detailed adult anatomy</option>
                </select>
            </label>
            <label class="text-sm font-medium">Description depth
                <select v-model="form.description_depth" class="mt-1 w-full rounded-md border bg-background p-2">
                    <option value="compact">Compact</option><option value="standard">Standard</option><option value="detailed">Detailed</option><option value="expanded">Expanded</option>
                </select>
            </label>
            <label class="text-sm font-medium">Character detail
                <select v-model="form.character_detail_level" class="mt-1 w-full rounded-md border bg-background p-2">
                    <option value="minimal">Minimal</option><option value="standard">Standard</option><option value="detailed">Detailed</option><option value="very_detailed">Very detailed</option>
                </select>
            </label>
            <label class="text-sm font-medium">Environment detail
                <select v-model="form.environment_detail_level" class="mt-1 w-full rounded-md border bg-background p-2">
                    <option value="minimal">Minimal</option><option value="standard">Standard</option><option value="detailed">Detailed</option><option value="rich">Richly detailed</option>
                </select>
            </label>
        </div>

        <label class="flex items-start gap-3 rounded-lg border p-3 text-sm">
            <input v-model="form.describe_every_visible_person" type="checkbox" class="mt-1" />
            <span><span class="font-medium">Describe every visible person individually</span><span class="mt-1 block text-muted-foreground">Makes group scenes more specific and consistent.</span></span>
        </label>

        <label class="text-sm font-medium">Additional generation instructions
            <Textarea v-model="form.additional_instructions" class="mt-1" rows="3" placeholder="Optional exclusions, details, or special instructions" />
        </label>

        <div v-if="showGenerate" class="flex items-center gap-3">
            <Button type="button" :disabled="generating || !canGenerate" @click="emit('generate')">{{ generating ? 'Generating...' : form.prompt_text ? 'Regenerate prompt' : 'Generate prompt' }}</Button>
            <span class="text-sm text-muted-foreground">Generation does not save until you click Save Prompt.</span>
        </div>

        <label class="text-sm font-medium">Prompt text
            <Textarea v-model="form.prompt_text" class="mt-1 min-h-[320px] leading-7" placeholder="Generate a prompt or write one manually" />
            <span v-if="form.errors?.prompt_text" class="mt-1 block text-sm text-destructive">{{ form.errors.prompt_text }}</span>
        </label>
    </div>
</template>

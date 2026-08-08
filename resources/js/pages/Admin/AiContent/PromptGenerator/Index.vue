<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Copy, Sparkles } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

import PageHeader from '@/Components/Shared/PageHeader.vue';
import ShowSection from '@/Components/Show/ShowSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{ recent: Array<any> }>();
const page = usePage<any>();
const output = computed(() => page.props.flash?.generated_prompt ?? '');
const copied = ref(false);
const hasGenerated = computed(() => output.value.trim() !== '');

const form = useForm({
    description: '',
    intended_use: 'blog_header',
    content_context: 'adult_naturism',
    output_mode: 'content_only',
    body_detail_level: 'detailed_adult_anatomy',
    description_depth: 'expanded',
    character_detail_level: 'very_detailed',
    environment_detail_level: 'detailed',
    describe_every_visible_person: true,
    orientation: 'landscape',
    additional_instructions: '',
});

watch(
    () => form.content_context,
    (context) => {
        if (context === 'adult_naturism') {
            form.body_detail_level = 'detailed_adult_anatomy';
            form.character_detail_level = 'very_detailed';
        } else if (context === 'family_naturism') {
            form.body_detail_level = 'natural_detail';
            form.character_detail_level = 'detailed';
        } else {
            form.body_detail_level = 'contextual';
            form.character_detail_level = 'detailed';
        }
    },
);

function submit() {
    form.post('/admin/ai-content/image-prompts', { preserveScroll: true });
}

async function copy() {
    if (!output.value) {
return;
}

    await navigator.clipboard.writeText(output.value);
    copied.value = true;
    setTimeout(() => (copied.value = false), 1500);
}

const bodyDetailHelp = computed(() => {
    if (form.body_detail_level === 'detailed_adult_anatomy') {
        return 'Best for adult naturism. Prompts should explicitly describe nude adults with strong neutral anatomy detail such as body type, body hair, pubic hair, breasts or chest, and for adult men when appropriate, flaccid penis and testicles.';
    }

    if (form.body_detail_level === 'natural_detail') {
        return 'A balanced mode. Prompts should clearly state that people are nude and describe realistic natural bodies while keeping the emphasis on the scene and activity.';
    }

    return 'A lighter mode that still states when people are nude, but keeps body description more scene-focused.';
});

const depthHelp = computed(() => ({
    compact: 'Short but specific prompt.',
    standard: 'Balanced prompt length.',
    detailed: 'More detailed people and surroundings.',
    expanded: 'Longest and richest prompts, best when you want strong person-by-person and environment detail.',
}[form.description_depth]));
</script>

<template>
    <Head title="AI Image Prompt Generator" />

    <div class="space-y-6 p-6">
        <PageHeader
            title="AI Image Prompt Generator"
            description="Create content-only, composition-aware, or full prompts from a short idea using the curated Unclad Collection prompt library."
        />

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <ShowSection title="Describe the image">
                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Short description</label>
                        <Textarea
                            v-model="form.description"
                            rows="8"
                            placeholder="Example: A family decorating a Christmas tree in a cozy cabin"
                        />
                        <p v-if="form.errors.description" class="text-sm text-destructive">
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="text-sm font-medium">
                            Intended use
                            <select v-model="form.intended_use" class="mt-1 w-full rounded-md border bg-background p-2">
                                <option value="blog_header">Blog header</option>
                                <option value="blog_inline">Blog inline</option>
                                <option value="general_image">General image</option>
                                <option value="collection_cover">Collection cover</option>
                                <option value="category_banner">Category banner</option>
                                <option value="advertisement">Advertisement</option>
                                <option value="social_media">Social media</option>
                                <option value="email_graphic">Email graphic</option>
                                <option value="landing_page">Landing page</option>
                            </select>
                        </label>

                        <label class="text-sm font-medium">
                            Context
                            <select v-model="form.content_context" class="mt-1 w-full rounded-md border bg-background p-2">
                                <option value="adult_naturism">Adult naturism</option>
                                <option value="family_naturism">Family naturism</option>
                                <option value="general">General</option>
                            </select>
                        </label>

                        <label class="text-sm font-medium">
                            Output mode
                            <select v-model="form.output_mode" class="mt-1 w-full rounded-md border bg-background p-2">
                                <option value="content_only">Content only</option>
                                <option value="content_composition">Content + composition</option>
                                <option value="full">Full prompt</option>
                            </select>
                        </label>

                        <label class="text-sm font-medium">
                            Orientation
                            <select v-model="form.orientation" class="mt-1 w-full rounded-md border bg-background p-2">
                                <option value="landscape">Landscape</option>
                                <option value="portrait">Portrait</option>
                                <option value="square">Square</option>
                                <option value="auto">Let AI decide</option>
                            </select>
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Body detail level</label>
                            <select v-model="form.body_detail_level" class="w-full rounded-md border bg-background p-2 text-sm">
                                <option value="contextual">Contextual</option>
                                <option value="natural_detail">Natural detail</option>
                                <option value="detailed_adult_anatomy">Detailed adult anatomy</option>
                            </select>
                            <p class="mt-2 text-sm text-muted-foreground">
                                {{ bodyDetailHelp }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Description depth</label>
                            <select v-model="form.description_depth" class="w-full rounded-md border bg-background p-2 text-sm">
                                <option value="compact">Compact</option>
                                <option value="standard">Standard</option>
                                <option value="detailed">Detailed</option>
                                <option value="expanded">Expanded / highly detailed</option>
                            </select>
                            <p class="mt-2 text-sm text-muted-foreground">
                                {{ depthHelp }}
                            </p>
                        </div>

                        <label class="text-sm font-medium">
                            Character detail level
                            <select v-model="form.character_detail_level" class="mt-1 w-full rounded-md border bg-background p-2">
                                <option value="minimal">Minimal</option>
                                <option value="standard">Standard</option>
                                <option value="detailed">Detailed</option>
                                <option value="very_detailed">Very detailed</option>
                            </select>
                        </label>

                        <label class="text-sm font-medium">
                            Environment detail level
                            <select v-model="form.environment_detail_level" class="mt-1 w-full rounded-md border bg-background p-2">
                                <option value="minimal">Minimal</option>
                                <option value="standard">Standard</option>
                                <option value="detailed">Detailed</option>
                                <option value="rich">Richly detailed</option>
                            </select>
                        </label>
                    </div>

                    <label class="flex items-start gap-3 rounded-lg border p-3 text-sm">
                        <input v-model="form.describe_every_visible_person" type="checkbox" class="mt-1" />
                        <span>
                            <span class="font-medium">Describe every visible person individually</span>
                            <span class="mt-1 block text-muted-foreground">
                                Helpful for better image quality when multiple people are present.
                            </span>
                        </span>
                    </label>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Additional instructions</label>
                        <Input v-model="form.additional_instructions" placeholder="Optional details, exclusions, or notes" />
                    </div>

                    <p v-if="form.content_context === 'family_naturism'" class="text-xs text-muted-foreground">
                        For family naturism, adult detail settings apply only to the adults. Minors remain context-only with private areas naturally obscured.
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button type="submit" :disabled="form.processing">
                            <Sparkles class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Generating...' : hasGenerated ? 'Regenerate prompt' : 'Generate prompt' }}
                        </Button>
                        <span v-if="hasGenerated" class="text-sm text-muted-foreground">
                            Change any options above and click regenerate to create a new version of the prompt.
                        </span>
                    </div>
                </form>
            </ShowSection>

            <ShowSection title="Generated prompt">
                <div v-if="output" class="space-y-4">
                    <div class="min-h-56 whitespace-pre-wrap rounded-xl border bg-muted/30 p-5 text-sm leading-7">
                        {{ output }}
                    </div>
                    <Button variant="outline" @click="copy">
                        <Copy class="mr-2 h-4 w-4" />
                        {{ copied ? 'Copied' : 'Copy prompt' }}
                    </Button>
                </div>
                <div v-else class="rounded-xl border border-dashed p-10 text-center text-muted-foreground">
                    Your generated prompt will appear here.
                </div>
            </ShowSection>
        </div>

        <ShowSection title="Recent generations">
            <div class="divide-y rounded-xl border">
                <div v-for="item in props.recent" :key="item.id" class="p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium">{{ item.input_text }}</p>
                        <span class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                            {{ item.input_context?.description_depth ?? 'n/a' }}
                        </span>
                        <span class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                            {{ item.input_context?.character_detail_level ?? 'n/a' }}
                        </span>
                    </div>
                    <p class="mt-1 line-clamp-2 text-sm text-muted-foreground">
                        {{ item.output_text || item.status }}
                    </p>
                </div>
            </div>
        </ShowSection>
    </div>
</template>

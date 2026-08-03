<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Ban, Check, Copy, LoaderCircle, Sparkles, WandSparkles } from '@lucide/vue';

import { Button } from '@/components/ui/button';

interface BlogAiResult {
    summary: string;
    excerpt: string;
    seo_title: string;
    seo_description: string;
    generated_tags: string[];
    readability: { score?: number; level?: string; strengths?: string[]; improvements?: string[] };
    clarity: { score?: number; strengths?: string[]; improvements?: string[] };
    publishing_review: { ready?: boolean; missing_items?: string[]; warnings?: string[]; recommended_actions?: string[] };
    header_image: { concept?: string; prompt?: string; alt_text?: string; caption?: string };
    inline_images: Array<{ placement?: string; purpose?: string; prompt?: string; alt_text?: string; caption?: string }>;
    internal_link_ideas: Array<{ anchor_text?: string; target_topic?: string; reason?: string }>;
}

const props = defineProps<{
    title: string;
    excerpt: string;
    content: string;
    blogPostId?: number | null;
    initialResult?: BlogAiResult | null;
    initialSettings?: Record<string, any> | null;
    initialAnalyzedAt?: string | null;
}>();

const emit = defineEmits<{
    (event: 'apply-excerpt', value: string): void;
    (event: 'apply-seo-title', value: string): void;
    (event: 'apply-seo-description', value: string): void;
    (event: 'analysis-updated', result: BlogAiResult, settings: Record<string, any>, analyzedAt: string): void;
    (event: 'apply-tags', tags: Array<{ id: number; name: string }>): void;
}>();

const loading = ref(false);
const error = ref('');
const result = ref<BlogAiResult | null>(props.initialResult ?? null);
const copiedKey = ref('');
const contentContext = ref(props.initialSettings?.content_context ?? 'adult_naturism');
const bodyDetailLevel = ref(props.initialSettings?.body_detail_level ?? 'detailed_adult_anatomy');
const descriptionDepth = ref(props.initialSettings?.description_depth ?? 'expanded');
const characterDetailLevel = ref(props.initialSettings?.character_detail_level ?? 'very_detailed');
const environmentDetailLevel = ref(props.initialSettings?.environment_detail_level ?? 'detailed');
const describeEveryVisiblePerson = ref(props.initialSettings?.describe_every_visible_person ?? true);
const analyzedAt = ref(props.initialAnalyzedAt ?? '');
const analysisSaved = ref(Boolean(props.blogPostId && props.initialResult));
const applyingTags = ref(false);
const selectedGeneratedTags = ref<string[]>([...(props.initialResult?.generated_tags ?? [])]);
const locallyExcludedTags = ref<string[]>([]);
const excludingTags = ref<string[]>([]);

watch(contentContext, (context) => {
    if (context === 'adult_naturism') {
        bodyDetailLevel.value = 'detailed_adult_anatomy';
        characterDetailLevel.value = 'very_detailed';
    } else if (context === 'family_naturism') {
        bodyDetailLevel.value = 'natural_detail';
        characterDetailLevel.value = 'detailed';
    } else {
        bodyDetailLevel.value = 'contextual';
        characterDetailLevel.value = 'detailed';
    }
});

const hasDraft = computed(() => props.title.trim() !== '' || props.content.replace(/<[^>]+>/g, '').trim() !== '');

const promptSettingsSummary = computed(() => [
    descriptionDepth.value.replace('_', ' '),
    characterDetailLevel.value.replace('_', ' '),
    environmentDetailLevel.value.replace('_', ' '),
].join(' · '));

const suggestedGeneratedTags = computed<string[]>(() => {
    const tags = result.value?.generated_tags ?? [];

    return tags.filter((tag) => !locallyExcludedTags.value.includes(tag));
});

watch(
    () => result.value?.generated_tags,
    (tags) => {
        selectedGeneratedTags.value = [...(tags ?? [])].filter(
            (tag) => !locallyExcludedTags.value.includes(tag),
        );
    },
);

function toggleGeneratedTag(tag: string): void {
    if (selectedGeneratedTags.value.includes(tag)) {
        removeGeneratedTag(tag);
        return;
    }

    selectedGeneratedTags.value = [...selectedGeneratedTags.value, tag];
}

function removeGeneratedTag(tag: string): void {
    if (!result.value) return;

    const normalized = tag.toLocaleLowerCase();

    result.value.generated_tags = result.value.generated_tags.filter(
        (item) => item.toLocaleLowerCase() !== normalized,
    );
    selectedGeneratedTags.value = selectedGeneratedTags.value.filter(
        (item) => item.toLocaleLowerCase() !== normalized,
    );

    emit(
        'analysis-updated',
        result.value,
        currentSettings(),
        analyzedAt.value || new Date().toISOString(),
    );
}

function updateLocalGeneratedTags(tagsToRemove: string[]): void {
    if (!result.value) return;

    const normalized = new Set(tagsToRemove.map((tag) => tag.toLocaleLowerCase()));
    result.value.generated_tags = result.value.generated_tags.filter(
        (tag) => !normalized.has(tag.toLocaleLowerCase()),
    );
    selectedGeneratedTags.value = selectedGeneratedTags.value.filter(
        (tag) => !normalized.has(tag.toLocaleLowerCase()),
    );
    locallyExcludedTags.value = Array.from(new Set([
        ...locallyExcludedTags.value,
        ...tagsToRemove,
    ]));

    emit(
        'analysis-updated',
        result.value,
        currentSettings(),
        analyzedAt.value || new Date().toISOString(),
    );
}

function excludeGeneratedTag(tag: string): void {
    if (excludingTags.value.includes(tag)) return;

    updateLocalGeneratedTags([tag]);
    excludingTags.value = [...excludingTags.value, tag];

    router.post('/admin/ai-keyword-exclusions', {
        keyword: tag,
        notes: 'Added directly from a Blog AI generated tag suggestion.',
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            locallyExcludedTags.value = locallyExcludedTags.value.filter((item) => item !== tag);
            if (result.value && !result.value.generated_tags.includes(tag)) {
                result.value.generated_tags = [...result.value.generated_tags, tag];
            }
        },
        onFinish: () => {
            excludingTags.value = excludingTags.value.filter((item) => item !== tag);
        },
    });
}

function normalizedScore(value: number | undefined): number {
    const numeric = Number(value ?? 0);

    if (!Number.isFinite(numeric)) return 0;

    const percentage = numeric > 0 && numeric <= 10 ? numeric * 10 : numeric;

    return Math.max(0, Math.min(100, Math.round(percentage)));
}

function readabilityLevel(score: number | undefined, suppliedLevel?: string): string {
    const normalized = normalizedScore(score);

    if (normalized >= 85) return 'Excellent';
    if (normalized >= 70) return 'Good';
    if (normalized > 0) return 'Needs work';

    return suppliedLevel || 'Not rated';
}

function currentSettings(): Record<string, any> {
    return {
        content_context: contentContext.value,
        body_detail_level: bodyDetailLevel.value,
        description_depth: descriptionDepth.value,
        character_detail_level: characterDetailLevel.value,
        environment_detail_level: environmentDetailLevel.value,
        describe_every_visible_person: describeEveryVisiblePerson.value,
    };
}

async function applyGeneratedTags(): Promise<void> {
    const names = [...selectedGeneratedTags.value];
    if (!names.length) return;

    applyingTags.value = true;
    error.value = '';

    try {
        const response = await fetch('/admin/blog-posts/ai-tags/resolve', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ names }),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message ?? 'The generated tags could not be applied.');
        emit('apply-tags', payload.tags ?? []);
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : 'The generated tags could not be applied.';
    } finally {
        applyingTags.value = false;
    }
}

async function analyze(): Promise<void> {
    loading.value = true;
    error.value = '';

    try {
        const response = await fetch('/admin/blog-posts/ai-assist', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({
                blog_post_id: props.blogPostId ?? null,
                title: props.title,
                excerpt: props.excerpt,
                content: props.content,
                content_context: contentContext.value,
                body_detail_level: bodyDetailLevel.value,
                description_depth: descriptionDepth.value,
                character_detail_level: characterDetailLevel.value,
                environment_detail_level: environmentDetailLevel.value,
                describe_every_visible_person: describeEveryVisiblePerson.value,
            }),
        });

        const responseText = await response.text();
        let payload: any = {};

        try {
            payload = responseText ? JSON.parse(responseText) : {};
        } catch {
            payload = {};
        }

        if (!response.ok) {
            const detail = payload.message
                ?? (responseText.trim() !== '' ? responseText.slice(0, 500) : `Request failed with HTTP ${response.status}`);
            throw new Error(detail);
        }

        if (!payload.result) {
            throw new Error('The Blog AI Assistant returned no analysis result.');
        }

        result.value = payload.result as BlogAiResult;
        analyzedAt.value = payload.analyzed_at ?? new Date().toISOString();
        analysisSaved.value = Boolean(payload.saved);
        emit('analysis-updated', result.value, payload.settings ?? currentSettings(), analyzedAt.value);
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : 'The Blog AI Assistant could not analyze this draft.';
    } finally {
        loading.value = false;
    }
}

async function copy(value: string | undefined, key: string): Promise<void> {
    if (!value) return;
    await navigator.clipboard.writeText(value);
    copiedKey.value = key;
    window.setTimeout(() => (copiedKey.value = ''), 1400);
}
</script>

<template>
    <div class="space-y-5">
        <div class="rounded-xl border bg-muted/20 p-5">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <div class="flex items-center gap-2 font-semibold">
                            <WandSparkles class="h-5 w-5" />
                            Analyze this article
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Generate readability and clarity feedback, SEO fields, a publishing review, and more detailed image prompts from the current draft.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:min-w-[620px] xl:grid-cols-3">
                        <label class="text-sm font-medium">
                            Content context
                            <select v-model="contentContext" class="mt-1 w-full rounded-md border bg-background p-2">
                                <option value="adult_naturism">Adult naturism</option>
                                <option value="family_naturism">Family naturism</option>
                                <option value="general">General</option>
                            </select>
                        </label>
                        <label class="text-sm font-medium">
                            Image body detail
                            <select v-model="bodyDetailLevel" class="mt-1 w-full rounded-md border bg-background p-2">
                                <option value="contextual">Contextual</option>
                                <option value="natural_detail">Natural detail</option>
                                <option value="detailed_adult_anatomy">Detailed adult anatomy</option>
                            </select>
                        </label>
                        <label class="text-sm font-medium">
                            Description depth
                            <select v-model="descriptionDepth" class="mt-1 w-full rounded-md border bg-background p-2">
                                <option value="compact">Compact</option>
                                <option value="standard">Standard</option>
                                <option value="detailed">Detailed</option>
                                <option value="expanded">Expanded / highly detailed</option>
                            </select>
                        </label>
                        <label class="text-sm font-medium">
                            Character detail
                            <select v-model="characterDetailLevel" class="mt-1 w-full rounded-md border bg-background p-2">
                                <option value="minimal">Minimal</option>
                                <option value="standard">Standard</option>
                                <option value="detailed">Detailed</option>
                                <option value="very_detailed">Very detailed</option>
                            </select>
                        </label>
                        <label class="text-sm font-medium">
                            Environment detail
                            <select v-model="environmentDetailLevel" class="mt-1 w-full rounded-md border bg-background p-2">
                                <option value="minimal">Minimal</option>
                                <option value="standard">Standard</option>
                                <option value="detailed">Detailed</option>
                                <option value="rich">Richly detailed</option>
                            </select>
                        </label>
                        <label class="flex items-center gap-3 rounded-md border bg-background px-3 py-2 text-sm font-medium xl:mt-6">
                            <input v-model="describeEveryVisiblePerson" type="checkbox" />
                            <span>Describe every visible person</span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                    <span class="rounded-full bg-background px-2.5 py-1">Prompt settings: {{ promptSettingsSummary }}</span>
                    <span v-if="analyzedAt && analysisSaved" class="rounded-full bg-background px-2.5 py-1">Saved analysis: {{ new Date(analyzedAt).toLocaleString() }}</span>
                    <span v-else-if="analyzedAt" class="rounded-full bg-background px-2.5 py-1">Analysis ready — save the blog post to keep it</span>
                    <span v-if="contentContext === 'family_naturism'" class="rounded-full bg-background px-2.5 py-1">
                        Adult detail applies only to adults; minors remain context-only.
                    </span>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <Button type="button" :disabled="loading || !hasDraft" @click="analyze">
                    <LoaderCircle v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
                    <Sparkles v-else class="mr-2 h-4 w-4" />
                    {{ loading ? 'Analyzing article...' : result ? 'Regenerate analysis' : 'Analyze article' }}
                </Button>
                <span v-if="result && !loading" class="text-sm text-muted-foreground">
                    Change any options above and click regenerate to create a new set of recommendations and image prompts.
                </span>
                <span v-if="!hasDraft" class="text-sm text-muted-foreground">Add a title or article content first.</span>
                <span v-if="error" class="text-sm text-destructive">{{ error }}</span>
            </div>
        </div>

        <template v-if="result">
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-xl border p-5">
                    <h3 class="font-semibold">Article summary</h3>
                    <p class="mt-3 whitespace-pre-wrap text-sm leading-6">{{ result.summary }}</p>
                </div>

                <div class="rounded-xl border p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-semibold">Recommended excerpt</h3>
                        <Button type="button" size="sm" variant="outline" @click="emit('apply-excerpt', result.excerpt)">
                            Apply excerpt
                        </Button>
                    </div>
                    <p class="mt-3 text-sm leading-6">{{ result.excerpt }}</p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-xl border p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold">Readability</h3>
                            <p class="text-sm text-muted-foreground">{{ normalizedScore(result.readability?.score) }}/100 · {{ readabilityLevel(result.readability?.score, result.readability?.level) }}</p>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-sm font-medium">Strengths</p>
                            <ul class="mt-2 space-y-1 text-sm text-muted-foreground">
                                <li v-for="item in result.readability?.strengths ?? []" :key="item">• {{ item }}</li>
                            </ul>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Improve</p>
                            <ul class="mt-2 space-y-1 text-sm text-muted-foreground">
                                <li v-for="item in result.readability?.improvements ?? []" :key="item">• {{ item }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border p-5">
                    <h3 class="font-semibold">Clarity</h3>
                    <p class="text-sm text-muted-foreground">{{ normalizedScore(result.clarity?.score) }}/100</p>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-sm font-medium">Strengths</p>
                            <ul class="mt-2 space-y-1 text-sm text-muted-foreground">
                                <li v-for="item in result.clarity?.strengths ?? []" :key="item">• {{ item }}</li>
                            </ul>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Improve</p>
                            <ul class="mt-2 space-y-1 text-sm text-muted-foreground">
                                <li v-for="item in result.clarity?.improvements ?? []" :key="item">• {{ item }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="suggestedGeneratedTags.length" class="rounded-xl border p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold">Generated tags</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Suggested from the article using the same shared ignore list, normalization, and deduplication rules as asset keywords.
                        </p>
                    </div>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        :disabled="applyingTags || selectedGeneratedTags.length === 0"
                        @click="applyGeneratedTags"
                    >
                        <LoaderCircle v-if="applyingTags" class="mr-2 h-4 w-4 animate-spin" />
                        {{ applyingTags ? 'Applying...' : `Apply selected tags (${selectedGeneratedTags.length})` }}
                    </Button>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <div
                        v-for="tag in suggestedGeneratedTags"
                        :key="tag"
                        class="flex items-center overflow-hidden rounded-full border bg-background"
                    >
                        <label class="flex cursor-pointer items-center gap-2 px-3 py-1.5 text-sm">
                            <input
                                type="checkbox"
                                :checked="selectedGeneratedTags.includes(tag)"
                                @change="toggleGeneratedTag(tag)"
                            />
                            <span>{{ tag }}</span>
                        </label>
                        <button
                            type="button"
                            class="flex self-stretch items-center border-l px-2 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive disabled:cursor-wait disabled:opacity-50"
                            :disabled="excludingTags.includes(tag)"
                            :aria-label="`Exclude ${tag} from future AI tag suggestions`"
                            :title="`Exclude “${tag}” from future AI suggestions`"
                            @click="excludeGeneratedTag(tag)"
                        >
                            <LoaderCircle v-if="excludingTags.includes(tag)" class="h-3.5 w-3.5 animate-spin" />
                            <Ban v-else class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div class="mt-4 border-t pt-3">
                    <p class="text-xs text-muted-foreground">
                        Unchecking a tag removes it from this analysis. Use the exclusion icon to remove it and add it to the same ignored-keyword list used by Assets.
                    </p>
                </div>
            </div>

            <div class="rounded-xl border p-5">
                <h3 class="font-semibold">SEO recommendations</h3>
                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-lg bg-muted/30 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-medium uppercase text-muted-foreground">SEO title</p>
                                <p class="mt-2 text-sm">{{ result.seo_title }}</p>
                            </div>
                            <Button type="button" size="sm" variant="outline" @click="emit('apply-seo-title', result.seo_title)">Apply</Button>
                        </div>
                    </div>
                    <div class="rounded-lg bg-muted/30 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-medium uppercase text-muted-foreground">SEO description</p>
                                <p class="mt-2 text-sm">{{ result.seo_description }}</p>
                            </div>
                            <Button type="button" size="sm" variant="outline" @click="emit('apply-seo-description', result.seo_description)">Apply</Button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border p-5">
                <div class="flex items-center gap-2">
                    <Check v-if="result.publishing_review?.ready" class="h-5 w-5" />
                    <h3 class="font-semibold">Publishing review</h3>
                    <span class="rounded-full bg-muted px-2 py-0.5 text-xs">{{ result.publishing_review?.ready ? 'Ready with review' : 'Needs attention' }}</span>
                </div>
                <div class="mt-4 grid gap-4 lg:grid-cols-3">
                    <div>
                        <p class="text-sm font-medium">Missing items</p>
                        <ul class="mt-2 space-y-1 text-sm text-muted-foreground"><li v-for="item in result.publishing_review?.missing_items ?? []" :key="item">• {{ item }}</li></ul>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Warnings</p>
                        <ul class="mt-2 space-y-1 text-sm text-muted-foreground"><li v-for="item in result.publishing_review?.warnings ?? []" :key="item">• {{ item }}</li></ul>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Recommended actions</p>
                        <ul class="mt-2 space-y-1 text-sm text-muted-foreground"><li v-for="item in result.publishing_review?.recommended_actions ?? []" :key="item">• {{ item }}</li></ul>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold">Header image concept</h3>
                        <p class="text-sm text-muted-foreground">{{ result.header_image?.concept }}</p>
                    </div>
                    <Button type="button" size="sm" variant="outline" @click="copy(result.header_image?.prompt, 'header')">
                        <Check v-if="copiedKey === 'header'" class="mr-2 h-4 w-4" /><Copy v-else class="mr-2 h-4 w-4" />
                        {{ copiedKey === 'header' ? 'Copied' : 'Copy prompt' }}
                    </Button>
                </div>
                <div class="mt-4 whitespace-pre-wrap rounded-lg bg-muted/30 p-4 text-sm leading-6">{{ result.header_image?.prompt }}</div>
                <div class="mt-3 grid gap-3 text-sm md:grid-cols-2">
                    <p><span class="font-medium">Alt text:</span> {{ result.header_image?.alt_text }}</p>
                    <p><span class="font-medium">Caption:</span> {{ result.header_image?.caption }}</p>
                </div>
            </div>

            <div v-if="result.inline_images?.length" class="rounded-xl border p-5">
                <h3 class="font-semibold">Inline image plan</h3>
                <div class="mt-4 space-y-4">
                    <div v-for="(image, index) in result.inline_images" :key="`${image.placement}-${index}`" class="rounded-lg bg-muted/30 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium">{{ image.placement }}</p>
                                <p class="mt-1 text-sm text-muted-foreground">{{ image.purpose }}</p>
                            </div>
                            <Button type="button" size="sm" variant="outline" @click="copy(image.prompt, `inline-${index}`)">
                                <Check v-if="copiedKey === `inline-${index}`" class="mr-2 h-4 w-4" /><Copy v-else class="mr-2 h-4 w-4" />
                                Copy
                            </Button>
                        </div>
                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6">{{ image.prompt }}</p>
                        <p class="mt-2 text-xs text-muted-foreground">Alt: {{ image.alt_text }}</p>
                    </div>
                </div>
            </div>

            <div v-if="result.internal_link_ideas?.length" class="rounded-xl border p-5">
                <h3 class="font-semibold">Internal link ideas</h3>
                <div class="mt-4 divide-y rounded-lg border">
                    <div v-for="link in result.internal_link_ideas" :key="`${link.anchor_text}-${link.target_topic}`" class="p-4">
                        <p class="font-medium">“{{ link.anchor_text }}” → {{ link.target_topic }}</p>
                        <p class="mt-1 text-sm text-muted-foreground">{{ link.reason }}</p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
